<?php

namespace TheFramework\Helpers;

use IteratorAggregate;
use Traversable;
use Closure;

/**
 * LazyCollection - Generator based memory efficient collection
 */
class LazyCollection implements IteratorAggregate
{
    public $source;

    public function __construct($source = null)
    {
        if (is_null($source)) {
            $this->source = function () {
                yield from [];
            };
        } elseif (is_array($source)) {
            $this->source = function () use ($source) {
                yield from $source;
            };
        } elseif ($source instanceof Closure) {
            $this->source = function () use ($source) {
                yield from $source();
            };
        } else {
            $this->source = function () use ($source) {
                yield from $source;
            };
        }
    }

    public static function make($source = null): self
    {
        return new static($source);
    }

    public function getIterator(): Traversable
    {
        return ($this->source)();
    }

    public function all(): array
    {
        $results = [];
        foreach ($this as $key => $value) {
            $results[] = $value;
        }
        return $results;
    }

    public function first(?callable $callback = null, $default = null)
    {
        foreach ($this as $key => $value) {
            if ($callback === null || $callback($value, $key)) {
                return $value;
            }
        }
        return $default;
    }

    public function last(?callable $callback = null, $default = null)
    {
        $result = $default;
        foreach ($this as $key => $value) {
            if ($callback === null || $callback($value, $key)) {
                $result = $value;
            }
        }
        return $result;
    }

    public function map(callable $callback): self
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    public function filter(?callable $callback = null): self
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback ? $callback($value, $key) : (bool) $value) {
                    yield $key => $value;
                }
            }
        });
    }

    public function reject(callable $callback): self
    {
        return $this->filter(function ($value, $key) use ($callback) {
            return !$callback($value, $key);
        });
    }

    public function chunkWhile(callable $callback): self
    {
        return new static(function () use ($callback) {
            $chunk = [];
            $first = true;

            foreach ($this as $key => $value) {
                if ($first) {
                    $chunk[] = $value;
                    $first = false;
                    continue;
                }

                if (!$callback($value, $key, $chunk)) {
                    yield new Collection($chunk);
                    $chunk = [];
                }

                $chunk[] = $value;
            }

            if (!empty($chunk)) {
                yield new Collection($chunk);
            }
        });
    }

    public function tapEach(callable $callback): self
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);
                yield $key => $value;
            }
        });
    }

    public function remember(): self
    {
        $iterator = $this->getIterator();
        $cached = [];

        return new static(function () use ($iterator, &$cached) {
            foreach ($cached as $key => $value) {
                yield $key => $value;
            }

            $it = $iterator instanceof \Iterator ? $iterator : new \IteratorIterator($iterator);
            
            // Only rewind if it hasn't been rewound yet and is not already active
            if (empty($cached)) {
                $it->rewind();
            }

            while ($it->valid()) {
                $key = $it->key();
                $value = $it->current();

                $cached[$key] = $value;

                yield $key => $value;

                $it->next();
            }
        });
    }

    public function each(callable $callback): self
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }
}
