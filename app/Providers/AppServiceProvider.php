<?php

namespace TheFramework\Providers;

use TheFramework\App\Core\Container;

class AppServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $container = Container::getInstance();
        // $container->singleton(SomeService::class, function() { ... });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Code to run after all services are registered
    }
}
