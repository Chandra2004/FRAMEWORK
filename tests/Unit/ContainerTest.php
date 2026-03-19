<?php

namespace Tests\Unit;

use Tests\TestCase;
use TheFramework\App\Core\Container;

class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Container is a singleton, so we flush it between tests
        Container::getInstance()->flush();
    }

    public function test_basic_binding()
    {
        $container = Container::getInstance();
        $container->bind('foo', function() {
            return 'bar';
        });

        $this->assertEquals('bar', $container->make('foo'));
    }

    public function test_singleton_binding()
    {
        $container = Container::getInstance();
        $container->singleton('foo', function() {
            return new \stdClass();
        });

        $instance1 = $container->make('foo');
        $instance2 = $container->make('foo');

        $this->assertSame($instance1, $instance2);
    }

    public function test_auto_wiring()
    {
        $container = Container::getInstance();
        
        // No binding needed for concrete classes
        $instance = $container->make(ContainerTestClass::class);
        
        $this->assertInstanceOf(ContainerTestClass::class, $instance);
    }

    public function test_dependency_injection()
    {
        $container = Container::getInstance();
        
        $instance = $container->make(ContainerDependencyClass::class);
        
        $this->assertInstanceOf(ContainerDependencyClass::class, $instance);
        $this->assertInstanceOf(ContainerTestClass::class, $instance->dep);
    }

    public function test_aliases()
    {
        $container = Container::getInstance();
        $container->bind('real_service', function() {
            return 'service';
        });
        $container->alias('real_service', 'alias_name');

        $this->assertEquals('service', $container->make('alias_name'));
    }

    public function test_tags()
    {
        $container = Container::getInstance();
        $container->bind('svc1', fn() => 'one');
        $container->bind('svc2', fn() => 'two');
        
        $container->tag(['svc1', 'svc2'], 'my_tags');
        
        $tagged = $container->tagged('my_tags');
        
        $this->assertCount(2, $tagged);
        $this->assertContains('one', $tagged);
        $this->assertContains('two', $tagged);
    }

    public function test_extenders()
    {
        $container = Container::getInstance();
        $container->bind('foo', fn() => 'bar');
        
        $container->extend('foo', function($old, $c) {
            return $old . ' baz';
        });
        
        $this->assertEquals('bar baz', $container->make('foo'));
    }

    public function test_contextual_binding()
    {
        $container = Container::getInstance();
        
        $container->when(ContainerContextualClass::class)
                  ->needs(ContainerTestInterface::class)
                  ->give(ContainerImplementationA::class);
        
        $instance = $container->make(ContainerContextualClass::class);
        $this->assertInstanceOf(ContainerImplementationA::class, $instance->impl);
    }

    public function test_circular_dependency_detection()
    {
        $this->expectException(\TheFramework\App\Exceptions\CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $container = Container::getInstance();
        $container->make(CircularA::class);
    }

    public function test_call_method_injection()
    {
        $container = Container::getInstance();
        
        $result = $container->call(function(ContainerTestClass $dep, $name = 'world') {
            return "hello {$name}";
        }, ['name' => 'container']);
        
        $this->assertEquals('hello container', $result);
    }
}

// Helper classes for testing
class ContainerTestClass {}

class ContainerDependencyClass {
    public $dep;
    public function __construct(ContainerTestClass $dep) {
        $this->dep = $dep;
    }
}

interface ContainerTestInterface {}
class ContainerImplementationA implements ContainerTestInterface {}

class ContainerContextualClass {
    public $impl;
    public function __construct(ContainerTestInterface $impl) {
        $this->impl = $impl;
    }
}

class CircularA {
    public function __construct(CircularB $b) {}
}
class CircularB {
    public function __construct(CircularA $a) {}
}
