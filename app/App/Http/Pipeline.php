<?php

namespace TheFramework\App\Http;

use TheFramework\App\Core\Container;

/**
 * Pipeline — Middleware Pipeline (Enterprise-Grade)
 * 
 * Menjalankan middleware secara berurutan dengan pattern:
 *   Middleware::handle(Request $request, Closure $next): Response
 * 
 * Ini memungkinkan setiap middleware untuk:
 * 1. Memodifikasi request SEBELUM diteruskan ke handler
 * 2. Memodifikasi response SETELAH dikembalikan dari handler
 * 3. Menghentikan pipeline (return response langsung tanpa $next)
 * 
 * @package TheFramework\App\Http
 * @version 5.1.0
 */
class Pipeline
{
    /**
     * The object being passed through the pipeline
     */
    protected Request $passable;

    /**
     * The array of middleware pipes
     */
    protected array $pipes = [];

    /**
     * The container instance
     */
    protected ?Container $container = null;

    // ========================================================
    //  BUILDER
    // ========================================================

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * Set the object being sent through the pipeline
     */
    public function send(Request $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the array of pipes (middleware)
     * 
     * Pipes bisa berupa:
     * - Full class name string: 'App\Http\Middleware\AuthMiddleware'
     * - Class name with parameters: ['App\Http\Middleware\RoleMiddleware', 'admin', 'editor']
     * - Instance of middleware object
     * - Closure: function(Request $request, Closure $next) { ... }
     * 
     * @param array $pipes
     */
    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback
     * 
     * @param \Closure $destination The final handler (controller action)
     * @return Response
     */
    public function then(\Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        $result = $pipeline($this->passable);

        return $this->ensureResponse($result);
    }

    /**
     * Run the pipeline and return the result
     * Alias for `then()` with a simple passthrough
     */
    public function thenReturn(): Response
    {
        return $this->then(function (Request $request) {
            return new Response('', 200);
        });
    }

    // ========================================================
    //  INTERNAL
    // ========================================================

    /**
     * Prepare the final destination closure
     */
    protected function prepareDestination(\Closure $destination): \Closure
    {
        return function (Request $request) use ($destination) {
            $result = $destination($request);
            return $this->ensureResponse($result);
        };
    }

    /**
     * Get a Closure that represents a slice of the onion (middleware layer)
     */
    protected function carry(): \Closure
    {
        return function (\Closure $stack, $pipe) {
            return function (Request $request) use ($stack, $pipe) {
                $result = null;

                if ($pipe instanceof \Closure) {
                    // Closure middleware
                    $result = $pipe($request, $stack);
                } else {
                    // Resolve middleware instance
                    [$instance, $parameters] = $this->resolvePipe($pipe);

                    if (method_exists($instance, 'handle')) {
                        // New-style: handle(Request, Closure, ...params): Response
                        $result = $instance->handle($request, $stack, ...$parameters);
                    } elseif (method_exists($instance, 'before')) {
                        // Legacy compatibility: before() + after() pattern
                        $instance->before();
                        $result = $stack($request);
                        if (method_exists($instance, 'after')) {
                            $instance->after();
                        }
                    } else {
                        // Middleware has no known method, skip it
                        $result = $stack($request);
                    }
                }

                return $this->ensureResponse($result);
            };
        };
    }

    /**
     * Resolve a middleware pipe to an instance + parameters
     * 
     * @param mixed $pipe
     * @return array [instance, parameters]
     */
    protected function resolvePipe(mixed $pipe): array
    {
        $parameters = [];

        if (is_object($pipe) && !($pipe instanceof \Closure)) {
            return [$pipe, $parameters];
        }

        if (is_array($pipe)) {
            $className = array_shift($pipe);
            $parameters = $pipe;
        } elseif (is_string($pipe) && str_contains($pipe, ':')) {
            [$className, $paramString] = explode(':', $pipe, 2);
            $parameters = explode(',', $paramString);
        } else {
            $className = $pipe;
        }

        // Resolve from container
        try {
            $instance = $this->container->make($className);
        } catch (\Throwable) {
            $instance = new $className();
        }

        return [$instance, $parameters];
    }

    /**
     * Ensure the result is a Response instance
     */
    protected function ensureResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return new Response($result, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return Response::json($result);
        }

        if (is_null($result)) {
            return new Response('', 200);
        }

        return new Response((string) $result, 200);
    }
}


/**
 * MiddlewareInterface — Base Contract for Middleware
 * 
 * Setiap middleware HARUS implement `handle()` method.
 * 
 * @example
 * class AuthMiddleware implements MiddlewareInterface {
 *     public function handle(Request $request, \Closure $next): Response {
 *         if (!auth()->check()) {
 *             return Response::redirect('/login');
 *         }
 *         return $next($request);
 *     }
 * }
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     * 
     * @param Request $request The incoming request
     * @param \Closure $next The next middleware/handler in the pipeline
     * @return Response The response
     */
    public function handle(Request $request, \Closure $next): Response;
}
