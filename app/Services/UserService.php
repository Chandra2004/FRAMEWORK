<?php

namespace TheFramework\Services;

use Exception;
use TheFramework\Repositories\UserRepository;
use TheFramework\Handlers\UploadHandler;
use TheFramework\Helpers\DatabaseHelper;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;

class UserService
{
    protected UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    public function status(): string
    {
        return DatabaseHelper::testConnection() ? 'success' : 'failed';
    }

    public function getAll()
    {
        return $this->repo->getAll();
    }

    public function getInformation(string $uid)
    {
        return $this->repo->getInformation($uid);
    }

    public function createUserService(UserRequest $request)
    {
        if ($this->repo->findByName($request->input('name')) != null) {
            throw new Exception('Name is taken');
        }

        if ($this->repo->findByEmail($request->input('email')) != null) {
            throw new Exception('Email is taken');
        }

        $photoName = null;
        if ($request->hasFile('profile_picture')) {
            $photoName = UploadHandler::handleUploadToWebP($request->file('profile_picture'), '/user-pictures', 'foto_');
            if (UploadHandler::isError($photoName)) {
                throw new Exception(UploadHandler::getErrorMessage($photoName));
            }
        }

        $data = $request->validated();
        if (array_key_exists('delete_profile_picture', $data)) {
            unset($data['delete_profile_picture']);
        }
        $data['profile_picture'] = $photoName;
        $data['uid'] = Helper::uuid();

        try {
            return $this->repo->createRepo($data);
        } catch (Exception $e) {
            if ($photoName) {
                UploadHandler::delete($photoName, '/user-pictures');
            }
            throw new Exception('Failed to save data:' . $e->getMessage());
        }
    }

    public function updateUserService(string $uid, UserRequest $request)
    {
        $existingUser = $this->repo->getInformation($uid);
        if (!$existingUser) {
            throw new Exception('User is not found');
        }

        if ($this->repo->findByName($request->input('name'), $uid) != null) {
            throw new Exception('Name is taken');
        }

        if ($this->repo->findByEmail($request->input('email'), $uid) != null) {
            throw new Exception('Email is taken');
        }

        $data = $request->validated();

        $oldPhoto = $existingUser['profile_picture'] ?? null;
        if (!empty($data['delete_profile_picture']) && $oldPhoto) {
            UploadHandler::delete($oldPhoto, '/user-pictures');
            $oldPhoto = null;
        }

        $newPhoto = null;
        if ($request->hasFile('profile_picture')) {
            if ($oldPhoto) {
                UploadHandler::delete($oldPhoto, '/user-pictures');
            }

            $newPhoto = UploadHandler::handleUploadToWebP($request->file('profile_picture'), '/user-pictures', 'foto_');
            if (UploadHandler::isError($newPhoto)) {
                throw new Exception(UploadHandler::getErrorMessage($newPhoto));
            }
        }

        if ($newPhoto) {
            $data['profile_picture'] = $newPhoto;
        } elseif (!empty($data['delete_profile_picture'])) {
            $data['profile_picture'] = null;
        } else {
            unset($data['profile_picture']);
        }
        unset($data['delete_profile_picture']);

        try {
            return $this->repo->updateRepo($data, $uid);
        } catch (Exception $e) {
            if ($newPhoto) {
                UploadHandler::delete($newPhoto, '/user-pictures');
            }
            throw new Exception('Failed to update data:' . $e->getMessage());
        }
    }

    public function deleteUserService(string $uid)
    {
        $existingUser = $this->repo->getInformation($uid);
        if (!$existingUser) {
            throw new Exception('User is not found');
        }

        try {
            $result = $this->repo->deleteRepo($uid);

            $photo = $existingUser['profile_picture'] ?? null;
            if ($photo) {
                UploadHandler::delete($photo, '/user-pictures');
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception('Failed to delete data: ' . $e->getMessage());
        }
    }
}
