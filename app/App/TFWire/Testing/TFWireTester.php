<?php

namespace TheFramework\App\TFWire\Testing;

use TheFramework\App\TFWire\Component;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Tester — Fluent Testing API                         ║
 * ║  Version: 2.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  Usage:                                                      ║
 * ║    TFWire::test(Counter::class)                              ║
 * ║      ->set('count', 5)                                       ║
 * ║      ->call('increment')                                     ║
 * ║      ->assertSet('count', 6)                                 ║
 * ║      ->assertSee('6')                                        ║
 * ║      ->assertEmitted('counted')                              ║
 * ║      ->assertHasNoErrors();                                  ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class TFWireTester
{
    private Component $component;
    private string $lastHtml = '';
    private array $assertions = [];
    private int $passed = 0;
    private int $failed = 0;

    public function __construct(string $componentClass, ?string $id = null)
    {
        if (!is_subclass_of($componentClass, Component::class)) {
            throw new \InvalidArgumentException("{$componentClass} is not a TFWire Component");
        }

        $this->component = new $componentClass($id ?? 'test-' . uniqid());
        $this->component->mount();
        $this->render();
    }

    // ═══════════════════════════════════════════════════════════
    //  STATE MANIPULATION
    // ═══════════════════════════════════════════════════════════

    /** Set a property value */
    public function set(string $property, mixed $value): static
    {
        if (!property_exists($this->component, $property)) {
            throw new \InvalidArgumentException("Property [{$property}] not found on component");
        }
        $this->component->{$property} = $value;
        $this->render();
        return $this;
    }

    /** Set multiple properties */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if (property_exists($this->component, $key)) {
                $this->component->{$key} = $value;
            }
        }
        $this->render();
        return $this;
    }

    /** Call an action on the component */
    public function call(string $method, ...$params): static
    {
        $this->component->callAction($method, $params);
        $this->render();
        return $this;
    }

    /** Simulate model input */
    public function type(string $property, string $value): static
    {
        return $this->set($property, $value);
    }

    /** Toggle a boolean property */
    public function toggle(string $property): static
    {
        $current = $this->component->{$property} ?? false;
        return $this->set($property, !$current);
    }

    // ═══════════════════════════════════════════════════════════
    //  ASSERTIONS
    // ═══════════════════════════════════════════════════════════

    /** Assert a property has a specific value */
    public function assertSet(string $property, mixed $expected): static
    {
        $actual = $this->component->{$property} ?? null;
        return $this->assert(
            $actual === $expected,
            "assertSet('{$property}')",
            "Expected [{$property}] to be " . var_export($expected, true) . ", got " . var_export($actual, true)
        );
    }

    /** Assert a property is NOT a specific value */
    public function assertNotSet(string $property, mixed $unexpected): static
    {
        $actual = $this->component->{$property} ?? null;
        return $this->assert(
            $actual !== $unexpected,
            "assertNotSet('{$property}')",
            "Expected [{$property}] to NOT be " . var_export($unexpected, true)
        );
    }

    /** Assert the rendered HTML contains a string */
    public function assertSee(string $text): static
    {
        return $this->assert(
            str_contains($this->lastHtml, $text),
            "assertSee('{$text}')",
            "Expected to see \"{$text}\" in rendered HTML"
        );
    }

    /** Assert the rendered HTML does NOT contain a string */
    public function assertDontSee(string $text): static
    {
        return $this->assert(
            !str_contains($this->lastHtml, $text),
            "assertDontSee('{$text}')",
            "Expected NOT to see \"{$text}\" in rendered HTML"
        );
    }

    /** Assert an event was emitted */
    public function assertEmitted(string $event): static
    {
        $emitted = array_column($this->component->getEventQueue(), 'event');
        return $this->assert(
            in_array($event, $emitted),
            "assertEmitted('{$event}')",
            "Expected event \"{$event}\" to be emitted. Emitted: [" . implode(', ', $emitted) . "]"
        );
    }

    /** Assert no event was emitted */
    public function assertNotEmitted(string $event): static
    {
        $emitted = array_column($this->component->getEventQueue(), 'event');
        return $this->assert(
            !in_array($event, $emitted),
            "assertNotEmitted('{$event}')",
            "Expected event \"{$event}\" NOT to be emitted"
        );
    }

    /** Assert there are no validation errors */
    public function assertHasNoErrors(): static
    {
        $errors = $this->component->getErrors();
        return $this->assert(
            empty($errors),
            "assertHasNoErrors()",
            "Expected no errors, got: " . json_encode($errors)
        );
    }

    /** Assert a specific field has a validation error */
    public function assertHasErrors(string|array $fields): static
    {
        $fields = is_string($fields) ? [$fields] : $fields;
        $errors = $this->component->getErrors();
        
        foreach ($fields as $field) {
            $this->assert(
                isset($errors[$field]) && !empty($errors[$field]),
                "assertHasErrors('{$field}')",
                "Expected [{$field}] to have validation errors, but none found"
            );
        }
        return $this;
    }

    /** Assert component would redirect */
    public function assertRedirect(?string $url = null): static
    {
        $redirectUrl = $this->component->getRedirectUrl();
        $condition = $url ? $redirectUrl === $url : $redirectUrl !== null;
        return $this->assert(
            $condition,
            "assertRedirect('{$url}')",
            $url 
                ? "Expected redirect to \"{$url}\", got \"{$redirectUrl}\"" 
                : "Expected a redirect, but none occurred"
        );
    }

    /** Assert no redirect */
    public function assertNoRedirect(): static
    {
        return $this->assert(
            $this->component->getRedirectUrl() === null,
            "assertNoRedirect()",
            "Expected no redirect, but got \"{$this->component->getRedirectUrl()}\""
        );
    }

    /** Assert property count (for arrays) */
    public function assertCount(string $property, int $expected): static
    {
        $value = $this->component->{$property} ?? [];
        $actual = is_countable($value) ? count($value) : 0;
        return $this->assert(
            $actual === $expected,
            "assertCount('{$property}', {$expected})",
            "Expected [{$property}] to have {$expected} items, got {$actual}"
        );
    }

    // ═══════════════════════════════════════════════════════════
    //  INTERNAL
    // ═══════════════════════════════════════════════════════════

    private function render(): void
    {
        $this->lastHtml = $this->component->render();
    }

    private function assert(bool $condition, string $name, string $failMessage): static
    {
        if ($condition) {
            $this->passed++;
            $this->assertions[] = ['status' => 'PASS', 'name' => $name];
        } else {
            $this->failed++;
            $this->assertions[] = ['status' => 'FAIL', 'name' => $name, 'message' => $failMessage];
        }
        return $this;
    }

    /** Get test results */
    public function getResults(): array
    {
        return [
            'passed'     => $this->passed,
            'failed'     => $this->failed,
            'total'      => $this->passed + $this->failed,
            'assertions' => $this->assertions,
        ];
    }

    /** Print test results to console */
    public function dump(): static
    {
        echo "\n  TFWire Test: " . get_class($this->component) . "\n";
        echo str_repeat('─', 50) . "\n";
        
        foreach ($this->assertions as $a) {
            $icon = $a['status'] === 'PASS' ? '  ✅' : '  ❌';
            echo "{$icon} {$a['name']}";
            if (isset($a['message'])) echo "\n     → {$a['message']}";
            echo "\n";
        }

        echo str_repeat('─', 50) . "\n";
        echo "  Result: {$this->passed} passed, {$this->failed} failed, " . ($this->passed + $this->failed) . " total\n\n";
        
        return $this;
    }

    /** Get the underlying component instance */
    public function instance(): Component
    {
        return $this->component;
    }

    /** Get the last rendered HTML */
    public function html(): string
    {
        return $this->lastHtml;
    }
}
