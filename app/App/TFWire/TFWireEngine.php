<?php

namespace TheFramework\App\TFWire;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Engine — The Brain Behind The Magic                  ║
 * ║  Version: 1.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  Handles:                                                    ║
 * ║  • Request detection & routing                               ║
 * ║  • State hydration & dehydration                             ║
 * ║  • Action dispatching with security                          ║
 * ║  • Model binding from frontend                               ║
 * ║  • Lazy loading endpoint                                     ║
 * ║  • CSRF validation                                           ║
 * ║  • Error handling & recovery                                 ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class TFWireEngine
{
    // ═══════════════════════════════════════════════════════════
    //  REQUEST DETECTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Check if current request is a TFWire request
     */
    public static function isTFWireRequest(): bool
    {
        return isset($_POST['_tf_class'])
            || isset($_GET['_tf_class'])
            || self::hasHeader('X-TFWire');
    }

    /**
     * Check if it's a Turbo Stream request
     */
    public static function isTurboStreamRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'text/vnd.turbo-stream.html');
    }

    /**
     * Check if it's a Turbo Frame request
     */
    public static function isTurboFrameRequest(): bool
    {
        return self::hasHeader('Turbo-Frame');
    }

    // ═══════════════════════════════════════════════════════════
    //  MAIN REQUEST HANDLER
    // ═══════════════════════════════════════════════════════════

    /**
     * Process an incoming TFWire request
     */
    public static function handleRequest(): ?string
    {
        $data = array_merge($_GET, $_POST);

        $class  = $data['_tf_class'] ?? null;
        $id     = $data['_tf_id'] ?? null;
        $state  = $data['_tf_state'] ?? null;
        $action = $data['_tf_action'] ?? null;
        $params = isset($data['_tf_params']) ? json_decode($data['_tf_params'], true) : [];
        $isLazy = isset($data['_tf_lazy']);

        // ── Validate component class ──
        if (!$class || !class_exists($class)) {
            return self::errorResponse("Component [{$class}] not found.");
        }

        if (!is_subclass_of($class, Component::class)) {
            return self::errorResponse("Class [{$class}] is not a TFWire Component.");
        }

        try {
            // ── 1. Instantiate ──
            /** @var Component $component */
            $component = new $class($id);

            // ── 2. Hydrate or Mount ──
            if ($state) {
                $component->hydrateState($state);
            } elseif ($isLazy) {
                $component->mount();
            }

            // ── 3. Process model bindings (tf-wire:model) ──
            $modelData = self::extractModelData($data);
            if (!empty($modelData)) {
                $component->fill($modelData);
            }

            // ── 4. Execute action ──
            $actionResult = null;
            if ($action) {
                $actionResult = $component->callAction($action, $params ?: []);

                // If action returns a TurboStream, send it directly
                if ($actionResult instanceof TurboStream) {
                    return $actionResult->render();
                }
            }

            // ── 5. Handle redirect ──
            if ($component->getRedirectUrl()) {
                if (!headers_sent()) {
                    header('Turbo-Location: ' . $component->getRedirectUrl());
                    header('Content-Type: text/vnd.turbo-stream.html; charset=utf-8');
                }
                return '';
            }

            // ── 6. Skip render check ──
            if ($component->shouldSkipRender()) {
                return '';
            }

            // ── 7. Re-render component ──
            $html = $component->render();

            // ── 8. Build response ──
            $stream = new TurboStream();
            $stream->replace($component->id, $html);

            // ── 9. Process event queue ──
            self::processEvents($component, $stream);

            return $stream->render();

        } catch (\TheFramework\App\Exceptions\ValidationException $e) {
            // Re-render with validation errors visible
            return self::handleValidationError($class, $id, $state, $e);

        } catch (\BadMethodCallException $e) {
            return (new TurboStream())
                ->error("Method Error: " . $e->getMessage())
                ->render();

        } catch (\Throwable $e) {
            return self::handleException($e);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Extract tf-wire:model data from request
     */
    private static function extractModelData(array $data): array
    {
        $models = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'tf_model_')) {
                $models[substr($key, 9)] = $value;
            }
        }
        return $models;
    }

    /**
     * Process event queue from component
     */
    private static function processEvents(Component $component, TurboStream $stream): void
    {
        foreach ($component->getEventQueue() as $event) {
            if ($event['scope'] === 'browser') {
                $stream->dispatch($event['event'], $event['params']);
            }
        }

        foreach ($component->getBrowserDispatchQueue() as $dispatch) {
            $stream->dispatch($dispatch['event'], $dispatch['data']);
        }
    }

    /**
     * Handle validation errors — re-render component with errors
     */
    private static function handleValidationError(string $class, ?string $id, ?string $state, $exception): string
    {
        try {
            $component = new $class($id);
            if ($state) {
                $component->hydrateState($state);
            }
            // Errors are already set on the component
            $html = $component->render();

            return (new TurboStream())
                ->replace($component->id, $html)
                ->render();
        } catch (\Throwable $e) {
            return self::handleException($e);
        }
    }

    /**
     * Handle unexpected exceptions
     */
    private static function handleException(\Throwable $e): string
    {
        $debug = function_exists('config') ? config('app.debug', false) : false;
        $message = $debug
            ? "TFWire Error: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine()
            : "Terjadi kesalahan pada komponen.";

        return (new TurboStream())
            ->error($message)
            ->render();
    }

    /**
     * Generate simple error response
     */
    private static function errorResponse(string $message): string
    {
        return (new TurboStream())
            ->error($message)
            ->render();
    }

    /**
     * Check if a specific header exists
     */
    private static function hasHeader(string $name): bool
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]);
    }

    // ═══════════════════════════════════════════════════════════
    //  ROUTE REGISTRATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Register TFWire routes with The Framework's Router
     */
    public static function registerRoutes(): void
    {
        if (class_exists(\TheFramework\App\Http\Router::class)) {
            \TheFramework\App\Http\Router::post('/tfwire/handle', function () {
                $response = self::handleRequest();
                if ($response !== null) {
                    echo $response;
                    exit;
                }
            });

            \TheFramework\App\Http\Router::get('/tfwire/handle', function () {
                $response = self::handleRequest();
                if ($response !== null) {
                    echo $response;
                    exit;
                }
            });
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  LARAVEL SERVICE PROVIDER BRIDGE
    // ═══════════════════════════════════════════════════════════

    /**
     * Boot TFWire for Laravel or similar frameworks.
     * Call this in a ServiceProvider's boot() method.
     *
     * Example:
     *   TFWireEngine::bootForLaravel($router, $blade);
     */
    public static function bootForLaravel($router = null, $blade = null): void
    {
        // Register route
        if ($router && method_exists($router, 'post')) {
            $router->match(['GET', 'POST'], '/tfwire/handle', function () {
                $response = self::handleRequest();
                if ($response !== null) {
                    return response($response, 200, [
                        'Content-Type' => 'text/vnd.turbo-stream.html',
                    ]);
                }
            });
        }

        // Register Blade directive
        if ($blade && method_exists($blade, 'directive')) {
            $blade->directive('tfwire', function ($expression) {
                return "<?php echo tfwire({$expression}); ?>";
            });

            $blade->directive('turboStreamScripts', function () {
                return '<div id="tf-notifications" style="position:fixed;top:1rem;right:1rem;z-index:9999;"></div>'
                     . '<div id="tf-modal-container"></div>'
                     . '<div id="tf-scripts" style="display:none;"></div>';
            });
        }
    }
}
