<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Config;

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
        $this->assertTrue(\TheFramework\Helpers\Helper::verify_password($password, $hash));
        $this->assertFalse(\TheFramework\Helpers\Helper::verify_password('wrong-pass', $hash));
    }

    public function test_helper_slugify()
    {
        $text = 'Judul Artikel Sederhana';
        $slug = \TheFramework\Helpers\Helper::slugify($text);

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
        $clean = \TheFramework\Helpers\Helper::sanitizeInput($dirty);

        $this->assertEquals('alert("XSS") Clean Text', $clean);
    }
}
