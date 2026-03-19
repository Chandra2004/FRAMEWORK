<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Core\Logging;
use TheFramework\App\Core\Config;

class LoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Logging::clearHistory();
    }

    public function test_basic_logging()
    {
        Logging::info('Test message');
        $history = Logging::getHistory();
        
        $this->assertCount(1, $history);
        $this->assertEquals('info', $history[0]['level']);
        $this->assertEquals('Test message', $history[0]['message']);
    }

    public function test_channel_logging()
    {
        Logging::on('custom')->error('Custom error');
        $history = Logging::getHistory();
        
        $this->assertCount(1, $history);
        $this->assertEquals('error', $history[0]['level']);
        $this->assertEquals('Custom error', $history[0]['message']);
    }

    public function test_context_masking()
    {
        Logging::info('User login', [
            'username' => 'chandra',
            'password' => 'secret123',
            'nested' => [
                'api_key' => '123456789'
            ]
        ]);
        
        $history = Logging::getHistory();
        $context = $history[0]['context'];
        
        $this->assertEquals('chandra', $context['username']);
        $this->assertEquals('********', $context['password']);
        $this->assertEquals('********', $context['nested']['api_key']);
    }

    public function test_history_limit()
    {
        for ($i = 0; $i < 150; $i++) {
            Logging::debug("Msg $i");
        }
        
        $this->assertCount(100, Logging::getHistory());
    }
}
