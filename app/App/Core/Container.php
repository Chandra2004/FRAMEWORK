<?php

namespace TheFramework\App\Core;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Container — Dependency Injection Container
 * 
 * Auto-wiring, singleton, contextual binding, method injection,
 * tagged bindings, dan resolving events.
 * 
 * @package TheFramework\App\Core
 * @version 5.0.1
 */
class Container
{
    private static ?self $instance = null;

    /**
     * Registered bindings
     * @var array<string, array{concrete: mixed, shared: bool}>
     */
    private array $bindings = [];

    /**
     * Shared instances (singletons)
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Aliases
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Tagged bindings
     * @var array<string, array<string>>
     */
    private array $tags = [];

    /**
     * Contextual bindings
     * @var array<string, array<string, mixed>>
     */
    private array $contextual = [];

    /**
     * Resolving callbacks
     * @var array<string, array<\Closure>>
     */
    private array $resolvingCallbacks = [];

    /**
     * After resolving callbacks
     * @var array<string, array<\Closure>>
     */
    private array $afterResolvingCallbacks = [];

    /**
     * Stack of classes being resolved (circular dep detection)
     */
    private array $buildStack = [];

    /**
     * Extending closures
     * @var array<string, array<\Closure>>
     */
    private array $extenders = [];

    // ========================================================
    //  SINGLETON PATTERN
    // ========================================================

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set global instance
     */
    public static function setInstance(?self $container): void
    {
        self::$instance = $container;
    }

    // ========================================================
    //  BINDING
    // ========================================================

    /**
     * Bind a class or interface to a resolver
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        $concrete = $concrete ?? $abstract;

        unset($this->instances[$abstract]);

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * Bind a singleton
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind jika belum ada
     */
    public function bindIf(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Singleton jika belum ada
     */
    public function singletonIf(string $abstract, mixed $concrete = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register an existing instance as shared
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = [
            'concrete' => $instance,
            'shared' => true,
        ];
        return $instance;
    }

    /**
     * Alias: buat alternative name untuk binding
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    // ========================================================
    //  RESOLVING
    // ========================================================

    /**
     * Resolve the given type from the container
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // Resolve alias
        $abstract = $this->getAlias($abstract);

        // Check shared instances
        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        // Get binding
        $binding = $this->bindings[$abstract] ?? [
            'concrete' => $abstract,
            'shared' => false,
        ];

        $concrete = $binding['concrete'];
        $isShared = $binding['shared'];

        // Fire resolving callbacks
        $this->fireResolvingCallbacks($abstract);

        // Build the object
        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } elseif (is_string($concrete) && $concrete !== $abstract) {
            $object = $this->make($concrete, $parameters);
        } elseif (is_string($concrete)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $concrete;
        }

        // Apply extenders
        foreach ($this->extenders[$abstract] ?? [] as $extender) {
            $object = $extender($object, $this);
        }

        // Store singleton
        if ($isShared && empty($parameters)) {
            $this->instances[$abstract] = $object;
        }

        // Fire after resolving callbacks
        $this->fireAfterResolvingCallbacks($abstract, $object);

        return $object;
    }

    /**
     * Resolve alias chain
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Build an instance via Reflection
     */
    protected function build(string $concrete, array $parameters = []): mixed
    {
        // Circular dependency detection
        if (in_array($concrete, $this->buildStack)) {
            $chain = implode(' → ', $this->buildStack) . ' → ' . $concrete;
            throw new \RuntimeException("Circular dependency detected: {$chain}");
        }

        $this->buildStack[] = $concrete;

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            array_pop($this->buildStack);
            throw new \RuntimeException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            array_pop($this->buildStack);
            throw new \RuntimeException("Target [{$concrete}] is not instantiable. Did you forget to bind it?");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            array_pop($this->buildStack);
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters, $concrete);

        array_pop($this->buildStack);
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve dependencies for parameters
     */
    public function resolveDependencies(array $dependencies, array $parameters = [], ?string $forClass = null): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // Check manual parameters
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            // Check positional parameters
            $position = $dependency->getPosition();
            if (array_key_exists($position, $parameters)) {
                $results[] = $parameters[$position];
                continue;
            }

            $type = $dependency->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                $results[] = $this->resolveScalar($dependency);
            } else {
                $typeName = $type->getName();

                // Check contextual binding
                if ($forClass && isset($this->contextual[$forClass][$typeName])) {
                    $contextValue = $this->contextual[$forClass][$typeName];
                    $results[] = ($contextValue instanceof \Closure) ? $contextValue($this) : $this->make($contextValue);
                    continue;
                }

                try {
                    $results[] = $this->make($typeName);
                } catch (\Throwable $e) {
                    if ($dependency->isDefaultValueAvailable()) {
                        $results[] = $dependency->getDefaultValue();
                    } elseif ($type->allowsNull()) {
                        $results[] = null;
                    } else {
                        throw new \RuntimeException(
                            "Unresolvable dependency [{$typeName}] in parameter " .
                            "\${$dependency->getName()}" .
                            ($forClass ? " of class [{$forClass}]" : '') . ": " . $e->getMessage(),
                            0,
                            $e
                        );
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Resolve scalar/primitive parameter
     */
    protected function resolveScalar(ReflectionParameter $dependency): mixed
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        if ($dependency->allowsNull()) {
            return null;
        }

        throw new \RuntimeException(
            "Unresolvable dependency: parameter \${$dependency->getName()} " .
            "has no type hint, no default value, and is not nullable."
        );
    }

    // ========================================================
    //  METHOD INJECTION (call)
    // ========================================================

    /**
     * Call a method/function/closure with automatic dependency injection
     * 
     * Contoh:
     *   $container->call([UserController::class, 'show'], ['id' => 5])
     *   $container->call(fn(UserService $svc) => $svc->all())
     *   $container->call('App\Services\UserService@getAll')
     */
    public function call(mixed $callback, array $parameters = []): mixed
    {
        // String format: "Class@method"
        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback, 2);
            $callback = [$this->make($class), $method];
        }

        // [ClassName, 'method'] → resolve class
        if (is_array($callback) && is_string($callback[0])) {
            $callback[0] = $this->make($callback[0]);
        }

        // Resolve dependencies
        if (is_array($callback)) {
            $reflector = new ReflectionMethod($callback[0], $callback[1]);
        } elseif ($callback instanceof \Closure) {
            $reflector = new ReflectionFunction($callback);
        } elseif (is_string($callback) && function_exists($callback)) {
            $reflector = new ReflectionFunction($callback);
        } else {
            throw new \RuntimeException("Invalid callback provided to Container::call()");
        }

        $deps = $this->resolveDependencies($reflector->getParameters(), $parameters);
        return call_user_func_array($callback, $deps);
    }

    // ========================================================
    //  CONTEXTUAL BINDING
    // ========================================================

    /**
     * Contextual binding builder
     * 
     * Contoh:
     *   $container->when(PhotoController::class)
     *             ->needs(FileSystem::class)
     *             ->give(LocalFileSystem::class);
     */
    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /**
     * Register contextual binding (internal)
     * @internal
     */
    public function addContextualBinding(string $concrete, string $abstract, mixed $implementation): void
    {
        $this->contextual[$concrete][$abstract] = $implementation;
    }

    // ========================================================
    //  TAGS
    // ========================================================

    /**
     * Tag bindings dengan label
     */
    public function tag(array|string $abstracts, string $tag): void
    {
        $abstracts = (array) $abstracts;
        foreach ($abstracts as $abstract) {
            $this->tags[$tag][] = $abstract;
        }
    }

    /**
     * Resolve semua bindings dengan tag tertentu
     */
    public function tagged(string $tag): array
    {
        $results = [];
        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $results[] = $this->make($abstract);
        }
        return $results;
    }

    // ========================================================
    //  EXTENDING
    // ========================================================

    /**
     * Extend a resolved binding (decorator pattern)
     * 
     * Contoh: $container->extend('cache', fn($cache, $c) => new LoggingCache($cache))
     */
    public function extend(string $abstract, \Closure $closure): void
    {
        $abstract = $this->getAlias($abstract);

        // Jika sudah ada instance, langsung apply
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);
        }

        $this->extenders[$abstract][] = $closure;
    }

    // ========================================================
    //  RESOLVING EVENTS
    // ========================================================

    /**
     * Register callback yang dipanggil SEBELUM resolving
     */
    public function resolving(string $abstract, \Closure $callback): void
    {
        $this->resolvingCallbacks[$abstract][] = $callback;
    }

    /**
     * Register callback yang dipanggil SETELAH resolving
     */
    public function afterResolving(string $abstract, \Closure $callback): void
    {
        $this->afterResolvingCallbacks[$abstract][] = $callback;
    }

    protected function fireResolvingCallbacks(string $abstract): void
    {
        foreach ($this->resolvingCallbacks[$abstract] ?? [] as $cb) {
            $cb($this);
        }
    }

    protected function fireAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        foreach ($this->afterResolvingCallbacks[$abstract] ?? [] as $cb) {
            $cb($object, $this);
        }
    }

    // ========================================================
    //  CONTAINER INSPECTION
    // ========================================================

    /**
     * Cek apakah abstract sudah di-bind
     */
    public function bound(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Alias untuk bound()
     */
    public function has(string $abstract): bool
    {
        return $this->bound($abstract);
    }

    /**
     * Cek apakah abstract sudah pernah di-resolve
     */
    public function resolved(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->instances[$abstract]);
    }

    /**
     * Cek apakah abstract di-bind sebagai shared (singleton)
     */
    public function isShared(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->instances[$abstract]) || ($this->bindings[$abstract]['shared'] ?? false);
    }

    /**
     * Dapatkan semua registered bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Dapatkan semua aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    // ========================================================
    //  CONTAINER MANAGEMENT
    // ========================================================

    /**
     * Hapus singleton instance
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Hapus semua singleton instances
     */
    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Reset container sepenuhnya
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->tags = [];
        $this->contextual = [];
        $this->resolvingCallbacks = [];
        $this->afterResolvingCallbacks = [];
        $this->extenders = [];
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Make dan langsung panggil method
     * 
     * Shortcut untuk: $container->call([$container->make(Foo::class), 'bar'])
     */
    public function makeAndCall(string $abstract, string $method, array $parameters = []): mixed
    {
        $instance = $this->make($abstract);
        return $this->call([$instance, $method], $parameters);
    }

    /**
     * Try to make — return null jika gagal (tidak throw)
     */
    public function tryMake(string $abstract, array $parameters = []): mixed
    {
        try {
            return $this->make($abstract, $parameters);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan dependency tree dari sebuah class
     * Berguna untuk debugging dependency issues
     */
    public function getDependencyTree(string $class, int $depth = 0, int $maxDepth = 5): array
    {
        if ($depth >= $maxDepth)
            return ['...max depth reached'];

        try {
            $reflector = new ReflectionClass($class);
        } catch (\Throwable) {
            return ['(unresolvable)'];
        }

        $constructor = $reflector->getConstructor();
        if (!$constructor)
            return [];

        $tree = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                $tree[$typeName] = $this->getDependencyTree($typeName, $depth + 1, $maxDepth);
            } else {
                $tree['$' . $param->getName()] = $type ? $type->getName() : 'mixed';
            }
        }

        return $tree;
    }

    /**
     * Formatted summary
     */
    public function summary(): string
    {
        $lines = [];
        $lines[] = "📊 Container Summary";
        $lines[] = str_repeat('─', 45);
        $lines[] = "   Bindings:    " . count($this->bindings);
        $lines[] = "   Singletons:  " . count($this->instances);
        $lines[] = "   Aliases:     " . count($this->aliases);
        $lines[] = "   Tags:        " . count($this->tags);
        $lines[] = "   Contextual:  " . count($this->contextual);

        return implode("\n", $lines);
    }
}

// ========================================================
//  CONTEXTUAL BINDING BUILDER
// ========================================================

/**
 * Fluent builder untuk contextual bindings
 */
class ContextualBindingBuilder
{
    private Container $container;
    private string $concrete;
    private string $needs;

    public function __construct(Container $container, string $concrete)
    {
        $this->container = $container;
        $this->concrete = $concrete;
    }

    /**
     * Define the abstract target
     */
    public function needs(string $abstract): static
    {
        $this->needs = $abstract;
        return $this;
    }

    /**
     * Define the implementation
     */
    public function give(mixed $implementation): void
    {
        $this->container->addContextualBinding($this->concrete, $this->needs, $implementation);
    }
}
