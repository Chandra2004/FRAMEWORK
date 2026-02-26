<?php

namespace TheFramework\Services;

use TheFramework\Repositories\UserRepository;
use TheFramework\Handlers\UploadHandler;
use TheFramework\Helpers\DatabaseHelper;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;

/**
 * UserService — Business Logic Layer
 *
 * Alur: Controller → Service → Repository → Model
 * Service menangani business logic + upload.
 * Repository menangani query database.
 *
 * @package TheFramework\Services
 * @version 5.0.3
 */
class UserService
{
    protected UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    /**
     * Cek status koneksi database.
     */
    public function status(): string
    {
        return DatabaseHelper::testConnection() ? 'success' : 'failed';
    }

    /**
     * Ambil semua user.
     */
    public function getAllUsers(): array
    {
        return $this->repo->getAll();
    }

    /**
     * Ambil user berdasarkan UID.
     */
    public function getUser(string $uid): ?array
    {
        return $this->repo->findByUid($uid);
    }

    /**
     * Ambil user berdasarkan email.
     */
    public function getUserByEmail(string $email): ?array
    {
        return $this->repo->findByEmail($email);
    }

    /**
     * Buat user baru dari request.
     *
     * @return bool|string|array true=sukses, string=error_key, array=upload_error
     */
    public function createFromRequest(UserRequest $request): bool|string|array
    {
        $validated = $request->validated();

        // 1. Business Logic: Check Uniqueness
        if ($this->repo->isNameTaken($validated['name'])) {
            return 'name_exist';
        }
        if ($this->repo->isEmailTaken($validated['email'])) {
            return 'email_exist';
        }

        // 2. Handle Upload (ke private-uploads/user-pictures)
        $uploadedFileName = null;

        if ($request->hasFile('profile_picture')) {
            $uploadResult = UploadHandler::handleUploadToWebP(
                $request->file('profile_picture'),
                '/user-pictures',
                'foto_'
            );

            if (UploadHandler::isError($uploadResult)) {
                return $uploadResult;
            }

            $validated['profile_picture'] = $uploadResult;
            $uploadedFileName = $uploadResult;
        } else {
            $validated['profile_picture'] = null;
        }

        // 3. Generate UID
        $validated['uid'] = Helper::uuid();

        // 4. Insert to DB via Repository
        $result = $this->repo->create($validated);

        if (!$result && $uploadedFileName) {
            // Cleanup upload on failure
            UploadHandler::delete($uploadedFileName, '/user-pictures');
        }

        return $result;
    }

    /**
     * Update user dari request.
     *
     * @return bool|string|array true=sukses, string=error_key, array=upload_error
     */
    public function updateFromRequest(string $uid, UserRequest $request): bool|string|array
    {
        $user = $this->repo->findByUid($uid);
        if (!$user) {
            return 'not_found';
        }

        $validated = $request->validated();

        // 1. Business Logic: Check Uniqueness (Exclude Self)
        if ($this->repo->isNameTaken($validated['name'], $uid)) {
            return 'name_exist';
        }
        if ($this->repo->isEmailTaken($validated['email'], $uid)) {
            return 'email_exist';
        }

        // 2. Handle Upload
        $oldProfilePicture = $user['profile_picture'] ?? null;
        $uploadedFileName = null;
        $deletePicture = !empty($validated['delete_profile_picture']);
        $profilePicture = $oldProfilePicture;

        if ($request->hasFile('profile_picture')) {
            $uploadResult = UploadHandler::handleUploadToWebP(
                $request->file('profile_picture'),
                '/user-pictures',
                'foto_'
            );

            if (UploadHandler::isError($uploadResult)) {
                return $uploadResult;
            }

            $uploadedFileName = $uploadResult;
            $profilePicture = $deletePicture ? null : $uploadedFileName;
        } else {
            $profilePicture = $deletePicture ? null : $oldProfilePicture;
        }

        // 3. Prepare Data
        unset($validated['delete_profile_picture']);
        $validated['profile_picture'] = $profilePicture;

        // 4. Update via Repository
        $result = $this->repo->update($validated, $uid);

        if ($result) {
            // Cleanup old file if replaced or deleted
            if (($deletePicture && $oldProfilePicture) || ($uploadedFileName && $oldProfilePicture)) {
                UploadHandler::delete($oldProfilePicture, '/user-pictures');
            }
        } else {
            // Cleanup new file if update failed
            if ($uploadedFileName) {
                UploadHandler::delete($uploadedFileName, '/user-pictures');
            }
        }

        return $result;
    }

    /**
     * Hapus user + cleanup file profile.
     */
    public function deleteUser(string $uid): bool
    {
        $user = $this->repo->findByUid($uid);

        // Hapus file profile picture jika ada
        if ($user && !empty($user['profile_picture'])) {
            UploadHandler::delete($user['profile_picture'], '/user-pictures');
        }

        return $this->repo->delete($uid);
    }
}
