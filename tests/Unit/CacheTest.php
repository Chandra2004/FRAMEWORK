<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Cache\CacheManager;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CacheManager::configure();
        CacheManager::driver('array'); // Use array driver for isolation
    }

    public function test_can_put_and_get_cache()
    {
        CacheManager::put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', CacheManager::get('test_key'));
    }

    public function test_can_check_existence()
    {
        CacheManager::put('exist_key', 'yes', 60);
        $this->assertTrue(CacheManager::has('exist_key'));
        $this->assertFalse(CacheManager::has('missing_key'));
    }

    public function test_can_forget_cache()
    {
        CacheManager::put('forget_me', 'data', 60);
        CacheManager::forget('forget_me');
        $this->assertFalse(CacheManager::has('forget_me'));
    }

    public function test_remember_pattern()
    {
        $executed = 0;
        $callback = function() use (&$executed) {
            $executed++;
            return 'dynamic_data';
        };

        // First call - executes callback
        $val1 = CacheManager::remember('mem_key', 60, $callback);
        $this->assertEquals('dynamic_data', $val1);
        $this->assertEquals(1, $executed);

        // Second call - retrieves from cache
        $val2 = CacheManager::remember('mem_key', 60, $callback);
        $this->assertEquals('dynamic_data', $val2);
        $this->assertEquals(1, $executed);
    }

    public function test_increment_and_decrement()
    {
        CacheManager::put('counter', 5, 0);
        CacheManager::increment('counter', 2);
        $this->assertEquals(7, CacheManager::get('counter'));

        CacheManager::decrement('counter', 1);
        $this->assertEquals(6, CacheManager::get('counter'));
    }
}
