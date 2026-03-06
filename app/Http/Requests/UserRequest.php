<?php

namespace TheFramework\Http\Requests;

use TheFramework\App\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Deteksi create vs update berdasarkan route parameter 'uid'
        $isCreate = empty(request()->route('uid'));

        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'password' => $isCreate ? 'required|min:8' : 'nullable|min:8',
            'profile_picture' => 'nullable|image|max:1024',
            'delete_profile_picture' => 'nullable'
        ];
    }

    public function labels(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email Address'
        ];
    }
}
