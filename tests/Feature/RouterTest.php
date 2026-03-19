<?php

namespace Tests\Feature;

use Tests\TestCase;
use TheFramework\App\Http\Router;

class RouterTest extends TestCase
{
    public function test_can_access_basic_get_route()
    {
        Router::get('/test-route', function() {
            return "hello world";
        });

        $this->get('/test-route')
             ->assertStatus(200)
             ->assertSee('hello world');
    }

    public function test_can_handle_route_parameters()
    {
        Router::get('/user/{id}', function($id) {
            return "User ID: {$id}";
        });

        $this->get('/user/42')
             ->assertStatus(200)
             ->assertSee('User ID: 42');
    }

    public function test_can_handle_post_request()
    {
        Router::post('/submit', function() {
            return "submitted";
        });

        $this->post('/submit', ['foo' => 'bar'])
             ->assertStatus(200)
             ->assertSee('submitted');
    }

    public function test_redirect_logic()
    {
        Router::redirect('/old-url', '/new-url', 302);

        $this->get('/old-url')
             ->assertStatus(302);
    }

    public function test_404_not_found()
    {
        $this->get('/non-existent-path')
             ->assertStatus(404);
    }
}
