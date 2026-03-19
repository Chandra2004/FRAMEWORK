<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Http\Validator;

class ValidatorTest extends TestCase
{
    public function test_basic_validation_passes()
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $rules = [
            'name' => 'required|string|min:3',
            'email' => 'required|email'
        ];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));
    }

    public function test_basic_validation_fails()
    {
        $data = ['name' => 'Jo', 'email' => 'not-an-email'];
        $rules = [
            'name' => 'required|string|min:3',
            'email' => 'required|email'
        ];

        $validator = new Validator();
        $this->assertFalse($validator->validate($data, $rules));
        $this->assertArrayHasKey('name', $validator->errors());
        $this->assertArrayHasKey('email', $validator->errors());
    }

    public function test_nullable_rule()
    {
        $data = ['email' => null];
        $rules = ['email' => 'nullable|email'];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));
    }

    public function test_required_if_rule()
    {
        $data = ['type' => 'company', 'company_name' => ''];
        $rules = [
            'type' => 'required',
            'company_name' => 'required_if:type,company'
        ];

        $validator = new Validator();
        $this->assertFalse($validator->validate($data, $rules));

        $data = ['type' => 'individual', 'company_name' => ''];
        $this->assertTrue($validator->validate($data, $rules));
    }

    public function test_numeric_and_integer_rules()
    {
        $data = ['age' => '25', 'price' => 10.5];
        $rules = [
            'age' => 'required|integer',
            'price' => 'required|numeric'
        ];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));

        $data = ['age' => '25.5'];
        $this->assertFalse($validator->validate($data, $rules));
    }

    public function test_confirmed_and_different()
    {
        $data = ['password' => 'secret', 'password_confirmation' => 'secret', 'old_password' => 'old_secret'];
        $rules = [
            'password' => 'confirmed|different:old_password'
        ];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));

        $data['password_confirmation'] = 'wrong';
        $this->assertFalse($validator->validate($data, $rules));
    }

    public function test_in_and_not_in()
    {
        $data = ['color' => 'red', 'size' => 'M'];
        $rules = [
            'color' => 'in:red,blue,green',
            'size' => 'not_in:S,L'
        ];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));

        $data['color'] = 'yellow';
        $this->assertFalse($validator->validate($data, $rules));
    }

    public function test_date_validation()
    {
        $data = ['start' => '2023-01-01', 'end' => '2023-12-31'];
        $rules = [
            'start' => 'date|date_format:Y-m-d',
            'end' => 'after:start'
        ];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));

        $data['end'] = '2022-01-01';
        $this->assertFalse($validator->validate($data, $rules));
    }

    public function test_regex_and_digits()
    {
        $data = ['code' => 'ABC-123', 'pin' => '1234'];
        $rules = [
            'code' => 'regex:/^[A-Z]{3}-\d{3}$/',
            'pin' => 'digits:4'
        ];

        $validator = new Validator();
        $this->assertTrue($validator->validate($data, $rules));

        $data['pin'] = '12345';
        $this->assertFalse($validator->validate($data, $rules));
    }

    public function test_wildcard_validation()
    {
        $data = [
            'users' => [
                ['email' => 'user1@test.com'],
                ['email' => 'user2@test.com'],
                ['email' => 'invalid-email']
            ]
        ];
        $rules = [
            'users.*.email' => 'required|email'
        ];

        $validator = new Validator();
        $this->assertFalse($validator->validate($data, $rules));
        $this->assertArrayHasKey('users.2.email', $validator->errors());
    }
}
