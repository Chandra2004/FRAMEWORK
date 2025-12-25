<?php

namespace TheFramework\Services;

use TheFramework\Models\HomeModel;
use TheFramework\Config\UploadHandler;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;

class UserService
{
    protected HomeModel $model;

    public function __construct()
    {
        $this->model = new HomeModel();
    }

    public function status()
    {
        return $this->model->Status();
    }

    public function getAllUsers()
    {
        return $this->model->GetAllUsers();
    }

    public function getUser(string $uid)
    {
        return $this->model->InformationUser($uid);
    }

    public function getUserByEmail(string $email)
    {
        // Menggunakan fitur Query Builder dasar dari Model
        // Asumsi Model punya method 'query()' yg me-return instance Query/Builder
        return $this->model->query()->where('email', '=', $email)->first();
    }

    public function createFromRequest(UserRequest $request)
    {
        $validated = $request->validated();
        $validated['uid'] = Helper::uuid();
        $uploadedFileName = null;

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

        $result = $this->model->UserAtomic($validated, '', 'create');
        if ($result === 'name_exist' || $result === 'email_exist') {
            if ($uploadedFileName) {
                UploadHandler::delete($uploadedFileName, '/user-pictures');
            }
            return $result;
        }

        if ($result === false) {
            if ($uploadedFileName) {
                UploadHandler::delete($uploadedFileName, '/user-pictures');
            }
            return false;
        }

        return $result;
    }

    public function updateFromRequest(string $uid, UserRequest $request)
    {
        $uploadedFileName = null;
        $oldProfilePicture = null;

        $user = $this->getUser($uid);
        if (empty($user)) {
            return 'not_found';
        }
        $oldProfilePicture = $user['profile_picture'] ?? null;

        $validated = $request->validated();
        $deletePicture = !empty($validated['delete_profile_picture']);

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

        unset($validated['delete_profile_picture']);
        $validated['profile_picture'] = $profilePicture;

        $result = $this->model->UserAtomic($validated, $uid, 'update');

        if ($result === true) {
            if ($deletePicture && $oldProfilePicture) {
                UploadHandler::delete($oldProfilePicture, '/user-pictures');
            } elseif ($uploadedFileName && $oldProfilePicture) {
                UploadHandler::delete($oldProfilePicture, '/user-pictures');
            }
        } else {
            if ($uploadedFileName) {
                UploadHandler::delete($uploadedFileName, '/user-pictures');
            }
        }

        return $result;
    }

    public function deleteUser(string $uid)
    {
        return $this->model->DeleteUser($uid);
    }
}
