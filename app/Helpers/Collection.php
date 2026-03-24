<?php

namespace TheFramework\Helpers;

use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;
use Countable;
use Closure;

/**
 * Collection - Paten Wrapper
 * Menyediakan antarmuka fluida untuk manipulasi data (Mirip Laravel Collection).
 */
class Collection implements ArrayAccess, IteratorAggregate, JsonSerializable, Countable
{
    protected array $items;

    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    public static function make($items = []): self
    {
        return new static($items);
    }

    protected function getArrayableItems($items): array
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        }

        return (array) $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function first(?callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return $default;
            }
            return reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public function last(?callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($this->items) ? $default : end($this->items);
        }

        return (new static(array_reverse($this->items, true)))->first($callback, $default);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function filter(?callable $callback = null): self
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        return new static(array_filter($this->items));
    }

    public function where($key, $operator = null, $value = null): self
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        } elseif (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $retrieved = $this->data_get($item, $key);

            switch ($operator) {
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
                default:
                    return $retrieved == $value;
            }
        });
    }

    public function whereIn($key, $values): self
    {
        $values = $this->getArrayableItems($values);
        return $this->filter(function ($item) use ($key, $values) {
            return in_array($this->data_get($item, $key), $values);
        });
    }

    public function sortBy($callback, $options = SORT_REGULAR, $descending = false): self
    {
        $results = [];
        $callback = $this->valueRetriever($callback);

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value);
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    public function sortByDesc($callback, $options = SORT_REGULAR): self
    {
        return $this->sortBy($callback, $options, true);
    }

    public function reverse(): self
    {
        return new static(array_reverse($this->items, true));
    }

    public function values(): self
    {
        return new static(array_values($this->items));
    }

    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    public function pluck($value, $key = null): self
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = $this->data_get($item, $value);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = $this->data_get($item, $key);
                $results[$itemKey] = $itemValue;
            }
        }

        return new static($results);
    }

    public function merge($items): self
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    public function push(...$values): self
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        return $this;
    }

    public function pull($key, $default = null)
    {
        $value = $this->offsetGet($key) ?? $default;
        $this->offsetUnset($key);
        return $value;
    }

    public function flatten($depth = INF): self
    {
        return new static($this->doFlatten($this->items, $depth));
    }

    protected function doFlatten($array, $depth)
    {
        $result = [];
        foreach ($array as $item) {
            $item = $item instanceof self ? $item->all() : $item;
            if (is_array($item)) {
                if ($depth === 1) {
                    $result = array_merge($result, $item);
                } else {
                    $result = array_merge($result, $this->doFlatten($item, $depth - 1));
                }
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function unique($key = null, $strict = false): self
    {
        if (is_null($key)) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $callback = $this->valueRetriever($key);
        $exists = [];
        return $this->filter(function ($item) use ($callback, $strict, &$exists) {
            $id = $callback($item);
            if (in_array($id, $exists, $strict)) {
                return false;
            }
            $exists[] = $id;
            return true;
        });
    }

    public function groupBy($groupBy): self
    {
        $callback = $this->valueRetriever($groupBy);
        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $callback($value, $key);
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                $results[$groupKey][] = $value;
            }
        }

        return new static(array_map(function ($group) {
            return new static($group);
        }, $results));
    }

    protected function valueRetriever($value): callable
    {
        if (is_string($value)) {
            return function ($item) use ($value) {
                return $this->data_get($item, $value);
            };
        }

        return $value;
    }

    protected function data_get($target, $key, $default = null)
    {
        if (is_null($key)) return $target;

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } elseif (is_object($target) && method_exists($target, 'getAttribute')) {
                $target = $target->getAttribute($segment);
            } else {
                return $default;
            }
        }

        return $target;
    }

    public function implode($value, $glue = null): string
    {
        $first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue ?? '', $this->pluck($value)->all());
        }

        return implode($value ?? '', $this->items);
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    // INTERFACE METHODS
    public function getIterator(): ArrayIterator { return new ArrayIterator($this->items); }
    public function offsetExists($offset): bool { return array_key_exists($offset, $this->items); }
    public function offsetGet($offset): mixed { return $this->items[$offset] ?? null; }
    public function offsetSet($offset, $value): void { 
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }
    public function offsetUnset($offset): void { unset($this->items[$offset]); }
    public function jsonSerialize(): mixed { 
        return array_map(function ($value) {
            if ($value instanceof self || (is_object($value) && method_exists($value, 'toArray'))) {
                return $value->toArray();
            }
            return $value;
        }, $this->items);
    }

    public function toArray(): array
    {
        return array_map(function ($value) {
            if ($value instanceof self || (is_object($value) && method_exists($value, 'toArray'))) {
                return $value->toArray();
            }
            return $value;
        }, $this->items);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
