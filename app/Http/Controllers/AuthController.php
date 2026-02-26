<?php

namespace TheFramework\Http\Controllers;

use TheFramework\Http\Controllers\Controller;

use TheFramework\Http\Requests\AuthRegisterRequest;
use TheFramework\App\Http\Request;
use TheFramework\Models\User; // Pastikan model User ada
use Exception;

class AuthController extends Controller
{
    public function register()
    {
        return view('auth.register', [
            'notification' => flash('notification'),
        ]);
    }

    public function registerProcess(AuthRegisterRequest $register)
    {
        // 1. Ambil data yang sudah divalidasi (Array Asosiatif)
        $validatedData = $register->validated();

        try {
            // TODO: Panggil Service untuk logic pendaftaran user.
            // Contoh logic sederhana di sini (biasanya di Service):

            // $user = User::create([
            //     'email' => $validatedData['email'],
            //     'password' => password_hash($validatedData['password'], PASSWORD_BCRYPT),
            //     'profile_data' => json_encode($validatedData)
            // ]);

            // --- SKENARIO 1: SUCCESS (Berhasil) ---
            return redirect('/login', 'success', 'Registrasi berhasil! Silakan login untuk melanjutkan.');

        } catch (Exception $e) {
            // --- SKENARIO 2: ERROR (Gagal) ---
            // Simpan input user agar tidak mengetik ulang
            session(['old_input' => $register->all()]);

            return redirect('/register', 'error', 'Gagal mendaftar: ' . $e->getMessage());
        }

        // --- SKENARIO 3: WARNING (Contoh jika perlu) ---
        // Biasanya digunakan kalau proses utama berhasil, tapi ada langkah optional yang gagal.
        /*
        Helper::redirect(
            '/login', 
            'warning', 
            'Akun dibuat, namun email verifikasi gagal dikirim.', 
            10
        );
        */
    }

    public function login()
    {
        return view('auth.login');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }
}
