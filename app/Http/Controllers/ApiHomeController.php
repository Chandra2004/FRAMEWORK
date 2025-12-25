<?php

namespace TheFramework\Http\Controllers;

use Exception;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Requests\UserRequest;
use TheFramework\Services\UserService;
use TheFramework\Config\UploadHandler;
use TheFramework\App\Request;

class ApiHomeController extends Controller
{
    private UserService $userService;
    private const CREATE_ERRORS = [
        'name_exist' => 'Nama sudah digunakan',
        'email_exist' => 'Email sudah digunakan',
    ];

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function Users()
    {
        try {
            $users = $this->userService->getAllUsers();
            return Helper::json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (Exception $e) {
            return Helper::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function InformationUser($uid)
    {
        $user = $this->userService->getUser($uid);

        if (empty($user)) {
            return Helper::json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        return Helper::json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function CreateUser()
    {
        try {
            $request = new UserRequest();
            // Validasi manual jika perlu, atau andalkan UserRequest (asumsi UserRequest aman)

            $resultUser = $this->userService->createFromRequest($request);

            // 1. Cek Hasil Upload Error (Array)
            if (is_array($resultUser) && UploadHandler::isError($resultUser)) {
                return Helper::json(['status' => 'error', 'message' => UploadHandler::getErrorMessage($resultUser)], 400);
            }

            // 2. Cek Gagal Simpan (False)
            if (!$resultUser) {
                return Helper::json(['status' => 'error', 'message' => 'Failed to create user in database'], 500);
            }

            // 3. Cek Error String (Duplicate)
            if (is_string($resultUser) && array_key_exists($resultUser, self::CREATE_ERRORS)) {
                return Helper::json(['status' => 'error', 'message' => self::CREATE_ERRORS[$resultUser]], 400);
            }

            // 4. Sukses -> Ambil Data User Baru
            // Kita coba ambil data user lengkap untuk frontend SPA
            $email = $request->input('email');
            $newUser = $this->userService->getUserByEmail($email);

            // Fallback jika entah kenapa gagal ambil (misal delay database), buat data dummy dari input
            if (!$newUser) {
                $newUser = [
                    'uid' => 'new_' . uniqid(),
                    'name' => $request->input('name'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'profile_picture' => null
                ];
            }

            return Helper::json([
                'status' => 'success',
                'message' => $request->input('name') . ' successfully created',
                'data' => $newUser
            ], 201);

        } catch (Exception $e) {
            // Pastikan return JSON, bukan stack trace HTML
            return Helper::json(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function UpdateUser($uid)
    {
        try {
            $request = new UserRequest();
            $result = $this->userService->updateFromRequest($uid, $request);

            if (is_array($result) && UploadHandler::isError($result)) {
                $errorMsg = UploadHandler::getErrorMessage($result);
                return Helper::json(['status' => 'error', 'message' => $errorMsg], 400);
            }

            if ($result === 'not_found') {
                return Helper::json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            if (is_string($result) && array_key_exists($result, self::CREATE_ERRORS)) {
                return Helper::json(['status' => 'error', 'message' => self::CREATE_ERRORS[$result]], 400);
            }

            if (!$result) {
                return Helper::json(['status' => 'error', 'message' => 'Failed to update user'], 500);
            }

            // Ambil data user terbaru
            $updatedUser = $this->userService->getUserByEmail($request->input('email')); // Email biasanya tidak berubah, atau kalau berubah ambil dari input

            return Helper::json([
                'status' => 'success',
                'message' => 'User successfully updated',
                'data' => $updatedUser
            ]);

        } catch (Exception $e) {
            return Helper::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function DeleteUser($uid)
    {
        try {
            $user = $this->userService->getUser($uid);
            if (!$user) {
                return Helper::json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            if (($user['profile_picture'] ?? null) != null) {
                UploadHandler::delete($user['profile_picture'], '/user-pictures');
            }

            $this->userService->deleteUser($uid);
            return Helper::json(['status' => 'success', 'message' => 'User successfully deleted']);
        } catch (Exception $e) {
            return Helper::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }



















}
