<?php

namespace TheFramework\Http\Requests;

use TheFramework\App\Request;

class OrdersRequest extends Request
{
    public function rules(): array
    {
        return [
            // 'name' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            // 'name.required' => 'Nama wajib diisi',
        ];
    }

    public function validated(): array
    {
        return $this->validate($this->rules(), $this->messages());
    }
}