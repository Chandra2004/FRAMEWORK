<?php

namespace TheFramework\App\Core;

/**
 * ServiceProvider — Base Service Provider (Enterprise-Grade)
 * 
 * Service providers adalah tempat sentral untuk mendaftarkan
 * binding, event listeners, middleware, routes, dll.
 * 
 * Lifecycle: register() → boot()
 * - register(): Bind ke container (JANGAN resolve dari container di sini)
 * - boot(): Resolve dan gunakan services (event, route, view, dll)
 * 
 * @example
 * class AuthServiceProvider extends ServiceProvider {
 *     public function register() {
 *         $this->container->singleton(Gate::class, fn() => new Gate());
 *     }
 *     
 *     public function boot() {
 *         Gate::define('admin', fn($user) => $user->isAdmin());
 *     }
 * }
 * 
 * @package TheFramework\App\Core
 * @version 5.1.0
 */
abstract class ServiceProvider
{
    /**
     * The container instance
     */
    protected Container $container;

    /**
     * Whether this provider has been booted
     */
    protected bool $booted = false;

    /**
     * Whether this provider has been registered
     */
    protected bool $registered = false;

    /**
     * Providers yang harus di-register SEBELUM provider ini
     */
    protected array $dependencies = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register bindings in the container
     * 
     * HANYA bind ke container di sini.
     * JANGAN resolve services di sini.
     */
    public function register(): void
    {
        // Override in subclass
    }

    /**
     * Bootstrap any application services
     * 
     * Di sini aman untuk resolve dari container,
     * mendaftarkan event listeners, routes, dll.
     */
    public function boot(): void
    {
        // Override in subclass
    }

    /**
     * Get provider dependencies
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Mark as registered
     */
    public function markRegistered(): void
    {
        $this->registered = true;
    }

    /**
     * Mark as booted
     */
    public function markBooted(): void
    {
        $this->booted = true;
    }

    public function isRegistered(): bool
    {
        return $this->registered;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Helper: Register a singleton binding
     */
    protected function singleton(string $abstract, $concrete = null): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    /**
     * Helper: Register a binding
     */
    protected function bind(string $abstract, $concrete = null): void
    {
        $this->container->bind($abstract, $concrete);
    }

    /**
     * Helper: Register an instance
     */
    protected function instance(string $abstract, $instance): void
    {
        $this->container->instance($abstract, $instance);
    }
}
