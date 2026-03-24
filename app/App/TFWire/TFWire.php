<?php

namespace TheFramework\App\TFWire;

use TheFramework\App\TFWire\Testing\TFWireTester;
use TheFramework\App\TFWire\Plugin\PluginManager;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Facade — Main Entry Point                           ║
 * ║                                                              ║
 * ║  Usage:                                                      ║
 * ║    TFWire::test(Counter::class)                              ║
 * ║    TFWire::plugin(RateLimiter::class)                        ║
 * ║    TFWire::version()                                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class TFWire
{
    public const VERSION = '2.0.0';

    /**
     * Create a test instance for a component
     * 
     * Usage:
     *   TFWire::test(Counter::class)
     *     ->set('count', 5)
     *     ->call('increment')
     *     ->assertSet('count', 6);
     */
    public static function test(string $componentClass, ?string $id = null): TFWireTester
    {
        return new TFWireTester($componentClass, $id);
    }

    /**
     * Register a plugin
     * 
     * Usage:
     *   TFWire::plugin(RateLimiter::class);
     */
    public static function plugin(string $pluginClass): void
    {
        PluginManager::register($pluginClass);
    }

    /**
     * Get version
     */
    public static function version(): string
    {
        return self::VERSION;
    }
}
