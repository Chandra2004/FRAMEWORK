<?php

namespace TheFramework\App\Http;

class Route
{
    protected int $index;

    public function __construct(int $index)
    {
        $this->index = $index;
    }

    public function name(string $name): self
    {
        Router::updateRoute($this->index, 'name', $name);
        return $this;
    }

    public function middleware($middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : func_get_args();
        Router::appendMiddleware($this->index, $middlewares);
        return $this;
    }
}
