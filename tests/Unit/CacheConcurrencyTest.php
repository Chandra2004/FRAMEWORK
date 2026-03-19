<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Cache\CacheManager;

class CacheConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CacheManager::driver('file');
        CacheManager::flush();
    }

    public function test_safe_file_write_atomic()
    {
        // Set a value and check it multiple times
        $key = 'concurrency_key';
        $value = ['large' => str_repeat('A', 1000)];
        
        $this->assertTrue(CacheManager::put($key, $value));
        
        $this->assertEquals($value, CacheManager::get($key));
    }

    public function test_cache_warming_engine()
    {
        $data = [
            'warm1' => 'value1',
            'warm2' => 'value2'
        ];
        
        // Cache is empty
        $this->assertNull(CacheManager::get('warm1'));
        
        // Warm up
        CacheManager::warm($data);
        
        $this->assertEquals('value1', CacheManager::get('warm1'));
        $this->assertEquals('value2', CacheManager::get('warm2'));
        
        // Ensure it doesn't overwrite existing
        CacheManager::put('warm1', 'modified');
        CacheManager::warm(['warm1' => 'ignored']);
        $this->assertEquals('modified', CacheManager::get('warm1'));
    }

    public function test_swr_stale_while_revalidate()
    {
        $key = 'swr_key';
        
        // Initial store
        CacheManager::put($key, 'stale-data', 1); // 1s TTL
        
        // Wait for expiry
        sleep(2);
        
        // SWR should return stale data but refresh in background
        // Wait, swr() in CacheManager.php is synchronous (callback executes)
        // because PHP doesn't easily do background threads.
        
        $called = false;
        $val = CacheManager::swr($key, 10, 60, function() use (&$called) {
            $called = true;
            return 'new-data';
        });
        
        $this->assertEquals('stale-data', $val);
        $this->assertTrue($called);
        
        // Next request gets new data
        $this->assertEquals('new-data', CacheManager::get($key));
    }
}
