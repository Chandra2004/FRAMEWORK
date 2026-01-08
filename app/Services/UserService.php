<?php

namespace TheFramework\Services;

use TheFramework\Models\User;
use TheFramework\Config\UploadHandler;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;

class UserService
{
    protected User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function status()
    {
        // Simple check via DB Helper or just try a query
        return \TheFramework\Helpers\DatabaseHelper::isConnected() ? 'success' : 'failed';
    }

    public function getAllUsers()
    {
        return $this->model->query()
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    public function getUser(string $uid)
    {
        return $this->model->find($uid);
    }

    public function getUserByEmail(string $email)
    {
        return $this->model->query()->where('email', '=', $email)->first();
    }

    public function createFromRequest(UserRequest $request)
    {
        $validated = $request->validated();
        $uploadedFileName = null;

        // 1. Business Logic: Check Uniqueness
        if ($this->model->query()->where('name', '=', $validated['name'])->first()) {
            return 'name_exist';
        }
        if ($this->model->query()->where('email', '=', $validated['email'])->first()) {
            return 'email_exist';
        }

        // 2. Handle Upload
        if ($request->hasFile('profile_picture')) {
            $uploadResult = UploadHandler::handleUploadToWebP($request->file('profile_picture'), '/user-pictures', 'foto_');
            if (UploadHandler::isError($uploadResult)) {
                return $uploadResult;
            }
            $validated['profile_picture'] = $uploadResult;
            $uploadedFileName = $uploadResult;
        } else {
            $validated['profile_picture'] = null;
        }

        // 3. Prepare Data
        $validated['uid'] = Helper::uuid();

        // 4. Insert to DB
        $result = $this->model->create($validated);

        if (!$result) {
            // Cleanup upload on failure
            if ($uploadedFileName) {
                UploadHandler::delete($uploadedFileName, '/user-pictures');
            }
            return false;
        }

        return $result;
    }

    public function updateFromRequest(string $uid, UserRequest $request)
    {
        $user = $this->getUser($uid);
        if (empty($user)) {
            return 'not_found';
        }

        $validated = $request->validated();

        // 1. Business Logic: Check Uniqueness (Exclude Self)
        if ($this->model->query()->where('name', '=', $validated['name'])->where('uid', '!=', $uid)->first()) {
            return 'name_exist';
        }
        if ($this->model->query()->where('email', '=', $validated['email'])->where('uid', '!=', $uid)->first()) {
            return 'email_exist';
        }

        $oldProfilePicture = $user['profile_picture'] ?? null;
        $uploadedFileName = null;
        $deletePicture = !empty($validated['delete_profile_picture']);

        // 2. Handle Upload
        $profilePicture = $oldProfilePicture;

        if ($request->hasFile('profile_picture')) {
            $uploadResult = UploadHandler::handleUploadToWebP($request->file('profile_picture'), '/user-pictures', 'foto_');
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

        // 4. Update DB
        $result = $this->model->update($validated, $uid);

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

    public function deleteUser(string $uid)
    {
        return $this->model->delete($uid);
    }
}
