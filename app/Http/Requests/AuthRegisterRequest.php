<?php

namespace TheFramework\Http\Requests;

use TheFramework\App\Http\FormRequest;

/**
 * Request validation untuk AuthRegisterRequest
 * 
 * Auto-validates when used in controller!
 * No need to call validate() manually.
 * 
 * Usage:
 * public function store(AuthRegisterRequest $request) {
 *     // Validation already done automatically
 *     // If we reach here, validation passed
 *     Model::create($request->validated());
 * }
 */
class AuthRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Return false to deny access (403 Forbidden).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Available rules: required, email, min, max, unique, exists,
     * alpha, numeric, confirmed, in, between, mimes, image, etc.
     * 
     * @return array
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => 'required|min:3|max:50',
            'no_telepon' => 'required|min:10|max:15|numeric',
            'email' => 'required|min:3|max:50|email',
            'tanggal_lahir' => 'required|string',
            'password' => 'required|min:6',
            'password_confirm' => 'required|min:6|same:password',
            'checkbox' => 'accepted'
        ];
    }

    /**
     * Get custom labels for validation error messages.
     * Makes error messages more user-friendly in your language.
     * 
     * @return array
     */
    public function labels(): array
    {
        return [
            'nama_lengkap' => 'Nama tidak valid. Harap periksa kembali.',
            'no_telepon' => 'Nomor telepon tidak valid. Harap periksa kembali.',
            'email' => 'Email tidak valid. Harap periksa kembali.',
            'tanggal_lahir' => 'Tanggal lahir tidak valid. Harap periksa kembali.',
            'password' => 'Kata sandi tidak valid. Harap periksa kembali.',
            'password_confirm' => 'Konfirmasi kata sandi tidak cocok.',
            'checkbox' => 'Anda harus menyetujui syarat dan ketentuan.'
        ];
    }
}
