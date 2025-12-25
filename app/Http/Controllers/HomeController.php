<?php

namespace TheFramework\Http\Controllers;

use Exception;
use TheFramework\App\View;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;
use TheFramework\Services\UserService;
use TheFramework\Config\UploadHandler;

class HomeController extends Controller
{
    private UserService $userService;
    private const CREATE_ERRORS = [
        'name_exist' => 'Nama sudah digunakan',
        'email_exist' => 'Email sudah digunakan',
    ];
    private const UPDATE_ERRORS = [
        'not_found' => 'User tidak ditemukan',
    ];

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function Welcome()
    {
        $notification = Helper::get_flash('notification');

        return View::render('interface.welcome', [
            'title' => 'THE FRAMEWORK - Modern PHP Framework with Database Migrations & REST API',
            'notification' => $notification,
            'status' => $this->userService->status()
        ]);
    }

    public function Users()
    {
        $notification = Helper::get_flash('notification');

        return View::render('interface.users', [
            'title' => 'THE FRAMEWORK - User Management',
            'notification' => $notification,
            'users' => $this->userService->getAllUsers()
        ]);
    }

    public function InformationUser($uid)
    {
        $notification = Helper::get_flash('notification');
        $user = $this->userService->getUser($uid);

        if (empty($user)) {
            return Helper::redirectToNotFound();
        }

        return View::render('interface.detail', [
            'title' => 'THE FRAMEWORK - ' . $user['name'] . ' - User Detail',
            'notification' => $notification,
            'user' => $user
        ]);
    }

    public function CreateUser()
    {
        if (Helper::is_post() && Helper::is_csrf()) {
            try {
                $request = new UserRequest();
                $resultUser = $this->userService->createFromRequest($request);

                if (is_array($resultUser) && UploadHandler::isError($resultUser)) {
                    $errorMsg = UploadHandler::getErrorMessage($resultUser);
                    return Helper::redirect('/users', 'error', "Upload gambar gagal: " . $errorMsg, 5);
                }

                if (!$resultUser) {
                    return Helper::redirect('/users', 'error', 'Failed to create user', 5);
                }

                if (is_string($resultUser) && array_key_exists($resultUser, self::CREATE_ERRORS)) {
                    return Helper::redirect('/users', 'error', 'error: ' . self::CREATE_ERRORS[$resultUser], 5);
                }

                return Helper::redirect('/users', 'success', $request->input('name') . ' successfully create', 5);
            } catch (Exception $e) {
                return Helper::redirect('/users', 'error', "Terjadi kesalahan: " . $e->getMessage(), 5);
            }
        }
    }

    public function UpdateUser($uid)
    {
        if (Helper::is_post() && Helper::is_csrf()) {
            try {
                $request = new UserRequest();
                $result = $this->userService->updateFromRequest($uid, $request);

                if (is_array($result) && UploadHandler::isError($result)) {
                    $errorMsg = UploadHandler::getErrorMessage($result);
                    return Helper::redirect("/users/information/{$uid}", 'error', "Upload gambar gagal: " . $errorMsg, 5);
                }

                if ($result === 'not_found') {
                    return Helper::redirect('/users', 'error', 'User not found', 5);
                }

                if (is_string($result) && array_key_exists($result, self::CREATE_ERRORS)) {
                    return Helper::redirect("/users/information/{$uid}", 'error', 'error: ' . self::CREATE_ERRORS[$result], 5);
                }

                if (!$result) {
                    return Helper::redirect("/users/information/{$uid}", 'error', 'Failed to update user', 5);
                }

                return Helper::redirect("/users/information/{$uid}", 'success', 'User successfully updated', 5);
            } catch (Exception $e) {
                return Helper::redirect("/users/information/{$uid}", 'error', "Terjadi kesalahan: " . $e->getMessage(), 5);
            }
        }
    }
    public function DeleteUser($uid)
    {
        if (Helper::is_post() && Helper::is_csrf()) {
            $user = $this->userService->getUser($uid);
            if ($user && ($user['profile_picture'] ?? null) != null) {
                UploadHandler::delete($user['profile_picture'], '/user-pictures');
            }

            $this->userService->deleteUser($uid);
            return Helper::redirect('/users', 'success', 'user berhasil terhapus', 5);
        }
    }




















}
