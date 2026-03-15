<?php

namespace TheFramework\App\Events;

/**
 * Dispatcher — Global Event System (Enterprise-Grade)
 * 
 * Event system terpusat untuk seluruh aplikasi.
 * Mendukung:
 * - Event listeners (1-to-many)
 * - Event subscribers (class-based grouping)
 * - Wildcard listeners
 * - Queued listeners (async via Queue)
 * - Event stopping (return false)
 * - Listener priorities
 * 
 * @example
 * // Register listener
 * Dispatcher::listen('user.login', function($user) {
 *     ActivityLog::log('login', $user);
 * });
 * 
 * // Dispatch event
 * Dispatcher::dispatch('user.login', $user);
 * 
 * // Class-based event
 * Dispatcher::listen(UserLoggedIn::class, SendWelcomeEmail::class);
 * 
 * // Wildcard
 * Dispatcher::listen('user.*', function($event, $data) { ... });
 * 
 * @package TheFramework\App\Events
 * @version 5.1.0
 */
class Dispatcher
{
    /**
     * Registered listeners [event => [priority => [callbacks]]]
     */
    protected static array $listeners = [];

    /**
     * Wildcard listeners [pattern => [callbacks]]
     */
    protected static array $wildcardListeners = [];

    /**
     * Registered subscribers
     */
    protected static array $subscribers = [];

    /**
     * Whether to enable event logging
     */
    protected static bool $logging = false;

    /**
     * Event log for debugging
     */
    protected static array $eventLog = [];

    // ========================================================
    //  LISTENER REGISTRATION
    // ========================================================

    /**
     * Register an event listener
     * 
     * @param string|array $events Event name(s) or class name(s)
     * @param callable|string|array $listener Callback, class name, or [class, method]
     * @param int $priority Higher = runs first (default: 0)
     */
    public static function listen(string|array $events, callable|string|array $listener, int $priority = 0): void
    {
        $events = is_array($events) ? $events : [$events];

        foreach ($events as $event) {
            if (str_contains($event, '*')) {
                static::$wildcardListeners[$event][] = [
                    'listener' => $listener,
                    'priority' => $priority,
                ];
            } else {
                static::$listeners[$event][$priority][] = $listener;
            }
        }
    }

    /**
     * Register multiple listeners at once
     * 
     * @example Dispatcher::listenMany([
     *     'user.login' => [LogLogin::class, NotifyAdmin::class],
     *     'user.register' => [SendWelcome::class],
     * ]);
     */
    public static function listenMany(array $map): void
    {
        foreach ($map as $event => $listeners) {
            $listeners = is_array($listeners) ? $listeners : [$listeners];
            foreach ($listeners as $listener) {
                static::listen($event, $listener);
            }
        }
    }

    /**
     * Register an event subscriber
     * 
     * A subscriber is a class that defines a `subscribe()` method
     * which receives the dispatcher and can register multiple listeners.
     * 
     * @example
     * class UserEventSubscriber {
     *     public function subscribe(Dispatcher $dispatcher) {
     *         $dispatcher::listen('user.login', [$this, 'onLogin']);
     *         $dispatcher::listen('user.logout', [$this, 'onLogout']);
     *     }
     * }
     */
    public static function subscribe(string|object $subscriber): void
    {
        $instance = is_string($subscriber) ? new $subscriber() : $subscriber;

        if (method_exists($instance, 'subscribe')) {
            $instance->subscribe(new static());
        }

        static::$subscribers[] = $instance;
    }

    // ========================================================
    //  DISPATCHING
    // ========================================================

    /**
     * Dispatch an event
     * 
     * @param string|object $event Event name or event object
     * @param mixed ...$payload Data to pass to listeners
     * @return array Results from all listeners
     */
    public static function dispatch(string|object $event, mixed ...$payload): array
    {
        $eventName = is_object($event) ? get_class($event) : $event;
        $results = [];

        // If event is an object, prepend it to payload
        if (is_object($event)) {
            array_unshift($payload, $event);
        }

        // Log event if enabled
        if (static::$logging) {
            static::$eventLog[] = [
                'event'     => $eventName,
                'timestamp' => microtime(true),
                'payload'   => count($payload),
            ];
        }

        // Gather all listeners (direct + wildcard)
        $listeners = static::getListeners($eventName);

        foreach ($listeners as $listener) {
            $result = static::callListener($listener, $eventName, $payload);

            if ($result !== null) {
                $results[] = $result;
            }

            // If listener returns false, stop propagation
            if ($result === false) {
                break;
            }
        }

        return $results;
    }

    /**
     * Dispatch event only if condition is true
     */
    public static function dispatchIf(bool $condition, string|object $event, mixed ...$payload): array
    {
        if ($condition) {
            return static::dispatch($event, ...$payload);
        }
        return [];
    }

    /**
     * Dispatch event unless condition is true
     */
    public static function dispatchUnless(bool $condition, string|object $event, mixed ...$payload): array
    {
        if (!$condition) {
            return static::dispatch($event, ...$payload);
        }
        return [];
    }

    /**
     * Fire event and return the first non-null result
     */
    public static function until(string|object $event, mixed ...$payload): mixed
    {
        $results = static::dispatch($event, ...$payload);

        foreach ($results as $result) {
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    // ========================================================
    //  LISTENER RESOLUTION
    // ========================================================

    /**
     * Get all listeners for an event (sorted by priority)
     */
    public static function getListeners(string $event): array
    {
        $listeners = [];

        // Direct listeners (sorted by priority, highest first)
        if (isset(static::$listeners[$event])) {
            $prioritized = static::$listeners[$event];
            krsort($prioritized);

            foreach ($prioritized as $priority => $group) {
                foreach ($group as $listener) {
                    $listeners[] = $listener;
                }
            }
        }

        // Wildcard listeners
        foreach (static::$wildcardListeners as $pattern => $wildcardGroup) {
            if (static::matchesWildcard($pattern, $event)) {
                foreach ($wildcardGroup as $item) {
                    $listeners[] = $item['listener'];
                }
            }
        }

        return $listeners;
    }

    /**
     * Call a listener
     */
    protected static function callListener(mixed $listener, string $eventName, array $payload): mixed
    {
        // Closure or callable
        if ($listener instanceof \Closure || is_callable($listener)) {
            return call_user_func($listener, ...$payload);
        }

        // [ClassName, methodName]
        if (is_array($listener) && count($listener) === 2) {
            [$class, $method] = $listener;
            $instance = is_string($class) ? new $class() : $class;
            return call_user_func([$instance, $method], ...$payload);
        }

        // Class name string — instantiate and call 'handle' method
        if (is_string($listener) && class_exists($listener)) {
            $instance = new $listener();

            if (method_exists($instance, 'handle')) {
                return $instance->handle(...$payload);
            }

            // Try __invoke
            if (is_callable($instance)) {
                return $instance(...$payload);
            }
        }

        return null;
    }

    /**
     * Check if pattern matches event name
     * 'user.*' matches 'user.login', 'user.register'
     */
    protected static function matchesWildcard(string $pattern, string $event): bool
    {
        $pattern = str_replace('.', '\\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        return (bool) preg_match("/^{$pattern}$/", $event);
    }

    // ========================================================
    //  MANAGEMENT
    // ========================================================

    /**
     * Check if there are listeners for an event
     */
    public static function hasListeners(string $event): bool
    {
        return !empty(static::getListeners($event));
    }

    /**
     * Remove all listeners for an event
     */
    public static function forget(string $event): void
    {
        unset(static::$listeners[$event]);
    }

    /**
     * Remove ALL listeners
     */
    public static function flush(): void
    {
        static::$listeners = [];
        static::$wildcardListeners = [];
        static::$subscribers = [];
        static::$eventLog = [];
    }

    // ========================================================
    //  DEBUGGING & LOGGING
    // ========================================================

    /**
     * Enable/disable event logging
     */
    public static function enableLogging(bool $enable = true): void
    {
        static::$logging = $enable;
    }

    /**
     * Get event log
     */
    public static function getLog(): array
    {
        return static::$eventLog;
    }

    /**
     * Clear event log
     */
    public static function clearLog(): void
    {
        static::$eventLog = [];
    }
}
