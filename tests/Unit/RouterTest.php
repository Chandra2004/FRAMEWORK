<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Http\Router;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Router::flush();
    }

    public function test_basic_route_registration()
    {
        Router::get('/test', 'TestController', 'index');
        
        $routes = Router::getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['path']);
    }

    public function test_named_route()
    {
        Router::get('/user/{id}', 'UserController', 'show')->name('user.show');
        
        $this->assertTrue(Router::hasRoute('user.show'));
        $url = Router::url('user.show', ['id' => 1]);
        $this->assertEquals('/user/1', $url);
    }

    public function test_route_groups()
    {
        Router::group(['prefix' => '/admin'], function() {
            Router::get('/dashboard', 'AdminController', 'index');
        });
        
        $routes = Router::getRoutes();
        $this->assertEquals('/admin/dashboard', $routes[0]['path']);
    }

    public function test_resource_routes()
    {
        Router::resource('posts', 'PostController');
        
        $this->assertTrue(Router::hasRoute('posts.index'));
        $this->assertTrue(Router::hasRoute('posts.show'));
        $this->assertTrue(Router::hasRoute('posts.store'));
        
        $url = Router::url('posts.show', ['id' => 123]);
        $this->assertEquals('/posts/123', $url);
    }

    public function test_url_generation_with_query_params()
    {
        Router::get('/search', 'SearchController', 'index')->name('search');
        
        $url = Router::url('search', ['q' => 'test', 'page' => 1]);
        $this->assertEquals('/search?q=test&page=1', $url);
    }
}
