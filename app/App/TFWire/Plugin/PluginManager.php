<?php

namespace TheFramework\App\TFWire\Plugin;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Plugin Manager — Extensible Hook System             ║
 * ║  Version: 2.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  Available Hooks:                                            ║
 * ║  • beforeAction($component, $action, $params)                ║
 * ║  • afterAction($component, $action, $result)                 ║
 * ║  • beforeRender($component)                                  ║
 * ║  • afterRender($component, &$html)                           ║
 * ║  • beforeHydrate($component, $state)                         ║
 * ║  • afterDehydrate($component, &$state)                       ║
 * ║  • onError($component, $exception)                           ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class PluginManager
{
    /** @var array<string, callable[]> */
    private static array $hooks = [];

    /** @var TFWirePlugin[] */
    private static array $plugins = [];

    /**
     * Register a plugin class
     */
    public static function register(string $pluginClass): void
    {
        if (!is_subclass_of($pluginClass, TFWirePlugin::class)) {
            throw new \InvalidArgumentException("[TFWire] {$pluginClass} must extend TFWirePlugin");
        }

        $plugin = new $pluginClass();
        self::$plugins[$pluginClass] = $plugin;
        $plugin->register(new static());
    }

    /**
     * Add a hook listener
     */
    public function on(string $hook, callable $callback, int $priority = 10): void
    {
        self::$hooks[$hook][] = ['callback' => $callback, 'priority' => $priority];
        
        // Sort by priority
        usort(self::$hooks[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Execute all listeners for a hook
     * 
     * @return mixed Last non-null return value, or null
     */
    public static function run(string $hook, ...$args): mixed
    {
        if (empty(self::$hooks[$hook])) return null;

        $result = null;
        foreach (self::$hooks[$hook] as $listener) {
            $ret = ($listener['callback'])(...$args);
            if ($ret !== null) $result = $ret;
            
            // If any listener returns false, stop propagation
            if ($ret === false) break;
        }

        return $result;
    }

    /**
     * Check if any plugin registered for a hook
     */
    public static function has(string $hook): bool
    {
        return !empty(self::$hooks[$hook]);
    }

    /**
     * Get all registered plugins
     */
    public static function getPlugins(): array
    {
        return self::$plugins;
    }

    /**
     * Reset all hooks (useful for testing)
     */
    public static function reset(): void
    {
        self::$hooks = [];
        self::$plugins = [];
    }
}
