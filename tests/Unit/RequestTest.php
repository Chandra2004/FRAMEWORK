<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Http\Request;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear superglobals for clean slate
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'localhost'
        ];
    }

    public function test_input_retrieval()
    {
        $_GET['name'] = 'John';
        $_POST['age'] = '25';
        
        $request = new Request();
        
        $this->assertEquals('John', $request->input('name'));
        $this->assertEquals('25', $request->input('age'));
        $this->assertEquals(['name' => 'John', 'age' => '25'], $request->all());
    }

    public function test_method_info()
    {
        $request = new Request();
        $this->assertTrue($request->isMethod('GET'));
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PUT';
        $request = new Request();
        $this->assertEquals('PUT', $request->method());
    }

    public function test_path_and_url()
    {
        $_SERVER['REQUEST_URI'] = '/users/profile?id=1';
        $request = new Request();
        
        $this->assertEquals('users/profile', $request->path());
        $this->assertEquals('http://localhost/users/profile', $request->url());
    }

    public function test_casting()
    {
        $_GET['active'] = '1';
        $_GET['count'] = '42';
        
        $request = new Request();
        
        $this->assertTrue($request->boolean('active'));
        $this->assertEquals(42, $request->integer('count'));
    }
}
