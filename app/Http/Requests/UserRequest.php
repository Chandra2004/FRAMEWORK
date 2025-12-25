<?php

namespace TheFramework\Http\Requests;

use TheFramework\App\Request;

class UserRequest extends Request
{
    /**
     * Aturan validasi untuk request ini.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'profile_picture' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'delete_profile_picture' => 'nullable'
        ];
    }

    /**
     * Pesan error kustom untuk validasi.
     */
    public function messages(): array
    {
        return [
            'name' => 'Name is required',
            'email' => 'Email is required'
        ];
    }

    public function validated(): array
    {
        return $this->validate($this->rules(), $this->messages());
    }
}
