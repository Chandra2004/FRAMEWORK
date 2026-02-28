<?php

namespace TheFramework\Http\Requests;

use TheFramework\App\Http\Request;

class UserRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'profile_picture' => 'nullable|file|images|max:2048',
        ];
    }

    public function updateRule(): array
    {
        return array_merge($this->rules(), [
            'delete_profile_picture' => 'nullable'
        ]);
    }

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

    public function updateValidated(): array
    {
        return $this->validate($this->updateRule(), $this->messages());
    }
}
