<?php

class MiddlewareManager
{
    private $middlewares = [];

    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function run($request, $final)
    {
        $next = $final;

        // Build pipeline in reverse order
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function () use ($middleware, $request, $next) {
                return $middleware->handle($request, $next);
            };
        }

        return $next();
    }
}
