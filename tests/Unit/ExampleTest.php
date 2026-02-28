<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Core\Config;

class ExampleTest extends TestCase
{
    public function test_basic_arithmetic()
    {
        $this->assertEquals(4, 2 + 2);
    }

    public function test_config_is_loaded()
    {
        $env = Config::get('APP_ENV');
        $this->assertEquals('testing', $env);
    }

    public function test_helper_encryption()
    {
        $password = 'secret123';
        $hash = \TheFramework\Helpers\Helper::hash_password($password);

        $this->assertNotEmpty($hash);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrong-pass', $hash));
    }

    public function test_helper_slugify()
    {
        $text = 'Judul Artikel Sederhana';
        $slug = \TheFramework\Helpers\Str::slug($text);

        $this->assertEquals('judul-artikel-sederhana', $slug);
    }

    public function test_helper_uuid()
    {
        $uuid1 = \TheFramework\Helpers\Helper::uuid();
        $uuid2 = \TheFramework\Helpers\Helper::uuid();

        $this->assertEquals(36, strlen($uuid1));
        $this->assertNotEquals($uuid1, $uuid2);
    }

    public function test_helper_sanitize_input()
    {
        $dirty = '   <script>alert("XSS")</script> Clean Text   ';
        // Helper::e() converts html chars but doesnt trim implicitly usually, let's just test e()
        $clean = \TheFramework\Helpers\Helper::e(trim($dirty));

        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt; Clean Text', $clean);
    }
}
