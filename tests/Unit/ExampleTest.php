<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Config;

class ExampleTest extends TestCase
{
    public function test_basic_arithmetic()
    {
        $this->assertTrue(true);
        $this->assertEquals(4, 2 + 2);
    }

    public function test_config_is_loaded()
    {
        // Karena di phpunit.xml kita set APP_ENV=testing
        // Kita assert env tersebut
        $env = Config::get('APP_ENV');
        $this->assertEquals('testing', $env);
    }
}
