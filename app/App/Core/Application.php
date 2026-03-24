<?php

namespace TheFramework\App\Core;

/**
 * Application — Application Kernel (Enterprise-Grade)
 * 
 * Extends Container dengan service provider management,
 * middleware stack, dan boot lifecycle.
 * 
 * @package TheFramework\App\Core
 * @version 5.1.0
 */
class Application extends Container
{
    /**
     * Application singleton instance
     */
    protected static ?Application $app = null;

    /**
     * Registered service providers
     */
    protected array $serviceProviders = [];

    /**
     * Provider class names that have been registered
     */
    protected array $loadedProviders = [];

    /**
     * Whether the application has been booted
     */
    protected bool $booted = false;

    /**
     * Global middleware stack
     */
    protected array $middleware = [];

    /**
     * Middleware groups ['web' => [...], 'api' => [...]]
     */
    protected array $middlewareGroups = [];

    /**
     * Route middleware aliases ['auth' => AuthMiddleware::class]
     */
    protected array $routeMiddleware = [];

    /**
     * Terminating callbacks
     */
    protected array $terminatingCallbacks = [];

    /**
     * Booting callbacks
     */
    protected array $bootingCallbacks = [];

    /**
     * Booted callbacks
     */
    protected array $bootedCallbacks = [];

    /**
     * The application version
     */
    protected string $version = '5.1.0';

    // ========================================================
    //  LIFECYCLE
    // ========================================================

    /**
     * Get the application singleton
     */
    public static function getInstance(): static
    {
        if (static::$app === null) {
            static::$app = new static();
        }
        return static::$app;
    }

    /**
     * Register a service provider
     */
    public function register(string|object $provider, bool $force = false): object
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $className = get_class($provider);

        // Already registered? Return existing
        if (!$force && isset($this->loadedProviders[$className])) {
            return $this->serviceProviders[$className];
        }

        // Register dependencies first (Native Providers only)
        if (method_exists($provider, 'getDependencies')) {
            foreach ($provider->getDependencies() as $dep) {
                if (!isset($this->loadedProviders[$dep])) {
                    $this->register($dep);
                }
            }
        }

        // Call register()
        if (method_exists($provider, 'register')) {
            $provider->register();
        }
        
        if (method_exists($provider, 'markRegistered')) {
            $provider->markRegistered();
        }

        $this->serviceProviders[$className] = $provider;
        $this->loadedProviders[$className] = true;

        // If already booted, boot this provider immediately
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Register many providers at once
     */
    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Boot all registered service providers
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Fire booting callbacks
        foreach ($this->bootingCallbacks as $callback) {
            $callback($this);
        }

        // Boot all providers
        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;

        // Fire booted callbacks
        foreach ($this->bootedCallbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * Boot a single provider
     */
    protected function bootProvider(object $provider): void
    {
        if (method_exists($provider, 'isBooted') && $provider->isBooted()) {
            return;
        }

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
        
        if (method_exists($provider, 'markBooted')) {
            $provider->markBooted();
        }
    }

    /**
     * Run the application lifecycle
     */
    public function run(): void
    {
        $this->boot();

        // Router will handle the request
        \TheFramework\App\Http\Router::run();

        // Run terminating callbacks
        $this->terminate();
    }

    /**
     * Register a terminating callback
     */
    public function terminating(callable $callback): void
    {
        $this->terminatingCallbacks[] = $callback;
    }

    /**
     * Execute terminating callbacks
     */
    public function terminate(): void
    {
        foreach ($this->terminatingCallbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * Register a callback to be run before booting
     */
    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a callback to be run after booting
     */
    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->booted) {
            $callback($this);
        }
    }

    // ========================================================
    //  MIDDLEWARE MANAGEMENT
    // ========================================================

    /**
     * Register global middleware
     */
    public function addMiddleware(string ...$middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * Register a middleware group
     */
    public function middlewareGroup(string $name, array $middleware): static
    {
        $this->middlewareGroups[$name] = $middleware;
        return $this;
    }

    /**
     * Add middleware to a group
     */
    public function pushMiddlewareTo(string $group, string ...$middleware): static
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }
        $this->middlewareGroups[$group] = array_merge($this->middlewareGroups[$group], $middleware);
        return $this;
    }

    /**
     * Register a route middleware alias
     */
    public function aliasMiddleware(string $name, string $class): static
    {
        $this->routeMiddleware[$name] = $class;
        return $this;
    }

    /**
     * Register multiple middleware aliases
     */
    public function aliasMiddlewareMany(array $aliases): static
    {
        foreach ($aliases as $name => $class) {
            $this->routeMiddleware[$name] = $class;
        }
        return $this;
    }

    /**
     * Get global middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get a middleware group
     */
    public function getMiddlewareGroup(string $name): array
    {
        return $this->middlewareGroups[$name] ?? [];
    }

    /**
     * Get all middleware groups
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    /**
     * Get route middleware by alias
     */
    public function getRouteMiddleware(string $alias): ?string
    {
        return $this->routeMiddleware[$alias] ?? null;
    }

    /**
     * Resolve middleware — handle aliases, groups, and class names
     */
    public function resolveMiddleware(array $middleware): array
    {
        $resolved = [];

        foreach ($middleware as $mw) {
            $name = is_array($mw) ? $mw[0] : $mw;
            $params = is_array($mw) ? array_slice($mw, 1) : [];

            // Check if it's a group
            if (isset($this->middlewareGroups[$name])) {
                $resolved = array_merge($resolved, $this->resolveMiddleware($this->middlewareGroups[$name]));
                continue;
            }

            // Check if it's an alias (possibly with params via :)
            if (is_string($name) && str_contains($name, ':')) {
                [$aliasName, $paramString] = explode(':', $name, 2);
                $params = array_merge($params, explode(',', $paramString));
                $name = $aliasName;
            }

            // Resolve alias
            if (isset($this->routeMiddleware[$name])) {
                $className = $this->routeMiddleware[$name];
                $resolved[] = !empty($params) ? array_merge([$className], $params) : $className;
                continue;
            }

            // Direct class name
            $resolved[] = !empty($params) ? array_merge([$name], $params) : $name;
        }

        return $resolved;
    }

    // ========================================================
    //  STATE & INFO
    // ========================================================

    /**
     * Check if the application has been booted
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get all registered providers
     */
    public function getProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * Check if a provider has been registered
     */
    public function providerIsRegistered(string $provider): bool
    {
        return isset($this->loadedProviders[$provider]);
    }

    /**
     * Get framework version
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Check if running in console
     */
    public function runningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    /**
     * Check environment
     */
    public function environment(string ...$environments): bool
    {
        $env = Config::get('APP_ENV', 'production');
        return in_array($env, $environments);
    }

    /**
     * Check if in production
     */
    public function isProduction(): bool
    {
        return $this->environment('production', 'prod');
    }

    /**
     * Check if in local/development
     */
    public function isLocal(): bool
    {
        return $this->environment('local', 'development', 'dev');
    }

    /**
     * Check if debug mode is on
     */
    public function isDebug(): bool
    {
        return Config::get('APP_DEBUG', false) === true
            || Config::get('APP_DEBUG', 'false') === 'true';
    }

    /**
     * Reset application state (for testing)
     */
    public function flush(): void
    {
        parent::flush();

        $this->serviceProviders = [];
        $this->loadedProviders = [];
        $this->booted = false;
        $this->middleware = [];
        $this->middlewareGroups = [];
        $this->routeMiddleware = [];
        $this->terminatingCallbacks = [];
        $this->bootingCallbacks = [];
        $this->bootedCallbacks = [];
    }
}
