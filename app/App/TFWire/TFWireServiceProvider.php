<?php

namespace TheFramework\App\TFWire;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Service Provider — Portable Bootstrapper             ║
 * ║  Version: 1.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  Works with:                                                 ║
 * ║  • The Framework (native)                                    ║
 * ║  • Laravel (via Service Provider)                            ║
 * ║  • Any PSR-compatible PHP framework                          ║
 * ║                                                              ║
 * ║  Usage:                                                      ║
 * ║  1. The Framework: TFWireServiceProvider::register()          ║
 * ║  2. Laravel: Add to config/app.php providers array            ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class TFWireServiceProvider
{
    /**
     * Register TFWire for The Framework (call in bootstrap/app.php)
     */
    public static function register(): void
    {
        // Register routes
        \TheFramework\App\TFWire\TFWireEngine::registerRoutes();
    }

    /**
     * Register TFWire for Laravel
     * 
     * In Laravel, create a proper ServiceProvider that calls these methods:
     * 
     * class TFWireServiceProvider extends ServiceProvider {
     *     public function boot() {
     *         \TheFramework\App\TFWire\TFWireServiceProvider::bootLaravel($this->app);
     *     }
     * }
     */
    public static function bootLaravel($app = null): void
    {
        if (!$app) return;

        // Register routes
        $router = $app->make('router');
        $router->match(['GET', 'POST'], '/tfwire/handle', function () {
            $response = \TheFramework\App\TFWire\TFWireEngine::handleRequest();
            if ($response !== null) {
                return response($response, 200)
                    ->header('Content-Type', 'text/vnd.turbo-stream.html');
            }
        })->middleware('web');

        // Register Blade directives
        if ($app->bound('blade.compiler')) {
            $blade = $app->make('blade.compiler');

            // @tfwire(ComponentClass::class, 'optional-id')
            $blade->directive('tfwire', function ($expression) {
                return "<?php echo tfwire({$expression}); ?>";
            });

            // @turboStreamTarget — include these in your layout
            $blade->directive('turboStreamTarget', function () {
                return '<div id="tf-notifications"></div>'
                     . '<div id="tf-modal-container"></div>'
                     . '<div id="tf-scripts" style="display:none;"></div>';
            });

            // @tfwireStyles
            $blade->directive('tfwireStyles', function () {
                return '<link rel="stylesheet" href="/assets/css/tf-wire.css">';
            });

            // @tfwireScripts
            $blade->directive('tfwireScripts', function () {
                return '<script src="/assets/js/tf-wire.js" defer></script>';
            });
        }

        // Register global helper function
        if (!function_exists('tfwire')) {
            require_once __DIR__ . '/../../Helpers/tfwire_helpers.php';
        }
    }

    /**
     * Get required asset paths for manual installation
     */
    public static function getAssets(): array
    {
        return [
            'css' => __DIR__ . '/../../../public/assets/css/tf-wire.css',
            'js'  => __DIR__ . '/../../../public/assets/js/tf-wire.js',
        ];
    }

    /**
     * Get layout HTML snippet for quick setup
     */
    public static function getLayoutSnippet(): string
    {
        return <<<'HTML'
<!-- TFWire: Add these to your layout -->

<!-- CSS (in <head>) -->
<link rel="stylesheet" href="/assets/css/tf-wire.css">

<!-- Turbo (before </body>) -->
<script type="module" src="https://unpkg.com/@hotwired/turbo@8.0.12/dist/turbo.es2017-esm.js"></script>

<!-- Alpine.js (before </body>) -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- TFWire Engine (before </body>) -->
<script src="/assets/js/tf-wire.js" defer></script>

<!-- TFWire Targets (inside <body>) -->
<div id="tf-notifications"></div>
<div id="tf-modal-container"></div>
<div id="tf-scripts" style="display:none;"></div>
HTML;
    }
}
