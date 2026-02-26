<?php

namespace TheFramework\Helpers;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;
use Countable;

/**
 * Collection - Paten Wrapper
 * Menyediakan antarmuka fluida untuk manipulasi data (Mirip Laravel Collection).
 */
class Collection implements ArrayAccess, IteratorAggregate, JsonSerializable, Countable
{
    protected array $items;

    public function __construct($items = [])
    {
        $this->items = (array) $items;
    }

    public static function make($items = []): self
    {
        return new static($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function first()
    {
        return reset($this->items);
    }

    public function last()
    {
        return end($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(callable $callback = null): self
    {
        if ($callback) return new static(array_filter($this->items, $callback));
        return new static(array_filter($this->items));
    }

    public function sort(callable $callback = null): self
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);
        return new static($items);
    }

    public function reverse(): self
    {
        return new static(array_reverse($this->items));
    }

    public function pluck(string $key): self
    {
        $results = array_map(function ($item) use ($key) {
            return is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
        }, $this->items);
        return new static($results);
    }

    public function toJson(): string
    {
        return json_encode($this->items);
    }

    // INTERFACE METHODS
    public function getIterator(): ArrayIterator { return new ArrayIterator($this->items); }
    public function offsetExists($offset): bool { return isset($this->items[$offset]); }
    public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
    public function offsetSet($offset, $value): void { $this->items[$offset] = $value; }
    public function offsetUnset($offset): void { unset($this->items[$offset]); }
    public function jsonSerialize(): mixed { return $this->items; }
}
