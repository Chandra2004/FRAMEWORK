<?php

namespace TheFramework\Http\Controllers;

use Exception;
use TheFramework\Http\Requests\UserRequest;
use TheFramework\Services\UserService;
use TheFramework\Handlers\UploadHandler;

/**
 * HomeController — Main Application Controller
 *
 * Arsitektur Clean: Controller → Service → Repository → Model
 * Controller hanya mengatur alur request/response.
 * Business logic ada di UserService.
 * Query database ada di UserRepository.
 *
 * @package TheFramework\Http\Controllers
 * @version 5.0.3
 */
class HomeController extends Controller
{
    private UserService $userService;

    private const CREATE_ERRORS = [
        'name_exist'  => 'Nama sudah digunakan',
        'email_exist' => 'Email sudah digunakan',
    ];

    private const UPDATE_ERRORS = [
        'not_found'   => 'User tidak ditemukan',
        'name_exist'  => 'Nama sudah digunakan',
        'email_exist' => 'Email sudah digunakan',
    ];

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * GET / — Halaman utama.
     */
    public function Welcome()
    {
        return view('interface.welcome', [
            'title'        => 'THE FRAMEWORK - Modern PHP Framework with Database Migrations & REST API',
            'notification' => flash('notification'),
            'status'       => $this->userService->status(),
        ]);
    }

    /**
     * GET /users — Daftar semua user.
     */
    public function Users()
    {
        return view('interface.users', [
            'title'        => 'THE FRAMEWORK - User Management',
            'notification' => flash('notification'),
            'users'        => $this->userService->getAllUsers(),
        ]);
    }

    /**
     * GET /users/information/{uid} — Detail user.
     */
    public function InformationUser($uid)
    {
        $user = $this->userService->getUser($uid);

        if (empty($user)) {
            return abort(404, 'User tidak ditemukan');
        }

        return view('interface.detail', [
            'title'        => 'THE FRAMEWORK - ' . $user['name'] . ' - User Detail',
            'notification' => flash('notification'),
            'user'         => $user,
        ]);
    }

    /**
     * POST /users/create — Buat user baru (dengan upload gambar).
     */
    public function CreateUser()
    {
        try {
            $request = new UserRequest();
            $result = $this->userService->createFromRequest($request);

            // Upload error
            if (UploadHandler::isError($result)) {
                return redirect('/users', 'error', 'Upload gambar gagal: ' . UploadHandler::getErrorMessage($result));
            }

            // Business logic error (name_exist, email_exist)
            if (is_string($result) && array_key_exists($result, self::CREATE_ERRORS)) {
                return redirect('/users', 'error', self::CREATE_ERRORS[$result]);
            }

            // DB insert failed
            if (!$result) {
                return redirect('/users', 'error', 'Gagal membuat user');
            }

            return redirect('/users', 'success', $request->input('name') . ' berhasil dibuat');
        } catch (Exception $e) {
            return redirect('/users', 'error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * POST /users/update/{uid} — Update user (dengan upload gambar).
     */
    public function UpdateUser($uid)
    {
        try {
            $request = new UserRequest();
            $result = $this->userService->updateFromRequest($uid, $request);

            // Upload error
            if (UploadHandler::isError($result)) {
                return redirect("/users/information/{$uid}", 'error', 'Upload gambar gagal: ' . UploadHandler::getErrorMessage($result));
            }

            // Business logic error
            if (is_string($result) && array_key_exists($result, self::UPDATE_ERRORS)) {
                return redirect("/users/information/{$uid}", 'error', self::UPDATE_ERRORS[$result]);
            }

            // DB update failed
            if (!$result) {
                return redirect("/users/information/{$uid}", 'error', 'Gagal mengupdate user');
            }

            return redirect("/users/information/{$uid}", 'success', 'User berhasil diupdate');
        } catch (Exception $e) {
            return redirect("/users/information/{$uid}", 'error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * POST /users/delete/{uid} — Hapus user (file cleanup otomatis di Service).
     */
    public function DeleteUser($uid)
    {
        try {
            $this->userService->deleteUser($uid);
            return redirect('/users', 'success', 'User berhasil dihapus');
        } catch (Exception $e) {
            return redirect('/users', 'error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }
}
