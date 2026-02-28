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
        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'profile_picture' => 'nullable|images|max:1024',
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
