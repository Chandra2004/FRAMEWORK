<?php

namespace App\Providers;

use TheFramework\App\Container;

class AppServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Example: $container = Container::getInstance();
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
