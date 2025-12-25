<?php

namespace TheFramework\App;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Exception;

class Container
{
    private static ?self $instance = null;
    /**
     * Bindings array structure:
     * [
     *    'abstract' => [
     *         'concrete' => Closure|string|object,
     *         'shared' => bool
     *    ]
     * ]
     */
    private array $bindings = [];
    private array $instances = [];

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bind a class or interface to a resolver.
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // Always wrap in an array structure
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared'   => $shared
        ];
    }

    /**
     * Bind a singleton instance.
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared.
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
        // Optionally mark as bound
        $this->bindings[$abstract] = [
            'concrete' => $instance,
            'shared'   => true
        ];
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract)
    {
        // 1. Check if we already have a shared instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Get binding info
        // If not bound, we assume the abstract is the concrete class
        $binding = $this->bindings[$abstract] ?? [
            'concrete' => $abstract,
            'shared'   => false
        ];

        $concrete = $binding['concrete'];
        $isShared = $binding['shared'];

        // 3. Build the object
        if ($concrete === $abstract || is_string($concrete)) {
            // Note: If concrete is string but not the same as abstract,
            // we could recursively call make($concrete) to resolve it properly (aliasing).
            // But for now, let's treat it as a class to build if they differ, or alias.
            if (is_string($concrete) && $concrete !== $abstract) {
                 // Recursive resolution (Alias support)
                 $object = $this->make($concrete);
            } else {
                 $object = $this->build($concrete);
            }
        } elseif ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            // It's already an object?
            $object = $concrete;
        }

        // 4. Store if shared
        if ($isShared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Build an instance of the given class using Reflection.
     */
    protected function build(string $concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve dependencies for a method or constructor.
     */
    public function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                     // For scalar types without default value, try to be null if optional
                     if ($dependency->isOptional()) {
                         $results[] = null;
                     } else {
                         // Cannot resolve
                         $results[] = null; 
                     }
                }
            } else {
                try {
                    $results[] = $this->make($type->getName());
                } catch (Exception $e) {
                    if ($dependency->isOptional()) {
                        $results[] = null;
                    } else {
                        throw $e;
                    }
                }
            }
        }

        return $results;
    }
}
