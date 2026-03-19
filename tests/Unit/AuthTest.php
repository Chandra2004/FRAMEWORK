<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Auth\AuthManager;
use TheFramework\App\Core\Config;
use TheFramework\App\Database\Model;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AuthManager::flush();
        Config::reset();
        $_SESSION = [];
    }

    public function test_login_and_check()
    {
        $user = new AuthUserMock();
        $user->id = 123;
        
        $this->assertFalse(AuthManager::check());
        
        AuthManager::login($user);
        
        $this->assertTrue(AuthManager::check());
        $this->assertEquals(123, AuthManager::id());
        $this->assertEquals($user, AuthManager::user());
    }

    public function test_logout()
    {
        $user = new AuthUserMock();
        $user->id = 123;
        
        AuthManager::login($user);
        $this->assertTrue(AuthManager::check());
        
        AuthManager::logout();
        
        $this->assertFalse(AuthManager::check());
        $this->assertNull(AuthManager::user());
    }

    public function test_custom_user_resolver()
    {
        $user = new AuthUserMock();
        $user->id = 999;
        
        AuthManager::resolveUsing(function() use ($user) {
            return $user;
        });
        
        $this->assertTrue(AuthManager::check());
        $this->assertEquals($user, AuthManager::user());
    }

    public function test_user_fail_throws_exception()
    {
        $this->expectException(\TheFramework\App\Exceptions\AuthenticationException::class);
        AuthManager::userOrFail();
    }

    public function test_login_callbacks()
    {
        $called = false;
        AuthManager::onLogin(function($user) use (&$called) {
            $called = true;
        });
        
        AuthManager::login(new AuthUserMock());
        $this->assertTrue($called);
    }
}

class AuthUserMock extends Model {
    public $id;
    public function getKey() { return $this->id; }

    // Mock find for loginById etc if needed
    public static function find($id) {
        $u = new static();
        $u->id = $id;
        return $u;
    }
}
