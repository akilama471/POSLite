<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, array $middlewares = []): void
    {
        $this->register("GET", $path, $handler, $middlewares);
    }

    public function post(string $path, string $handler, array $middlewares = []): void
    {
        $this->register("POST", $path, $handler, $middlewares);
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?: "/";
        $method = strtoupper($method);

        foreach ($this->routes[$method] ?? [] as $route) {
            $pattern = $this->convertToRegex($route["path"]);

            if (preg_match($pattern, $uri, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            $this->callHandler($route["handler"], $matches, $route["middlewares"]);
            return;
        }

        http_response_code(404);
        View::make("errors/404", ["title" => "Not Found"]);
    }

    private function register(
        string $method,
        string $path,
        string $handler,
        array $middlewares = [],
    ): void {
        $this->routes[$method][] = [
            "path" => $path,
            "handler" => $handler,
            "middlewares" => $middlewares,
        ];
    }

    private function convertToRegex(string $path): string
    {
        $pattern = preg_replace("#\{([\w]+)\}#", "([\w-]+)", $path);
        return "#^" . $pattern . "$#";
    }

    private function callHandler(string $handler, array $params, array $middlewares): void
    {
        [$controllerName, $method] = explode("@", $handler, 2);

        if (!class_exists($controllerName)) {
            throw new RuntimeException("Controller not found: {$controllerName}");
        }

        $controller = new $controllerName();
        $request = new Request();

        $final = function () use ($controller, $method, $params, $request) {
            $arguments = array_merge([$request], $params);
            return call_user_func_array([$controller, $method], $arguments);
        };

        if ($middlewares === []) {
            $final();
            return;
        }

        $manager = new MiddlewareManager();

        foreach ($middlewares as $middleware) {
            $manager->add($this->resolveMiddleware($middleware));
        }

        $manager->run($request, $final);
    }

    private function resolveMiddleware(string $name): Middleware
    {
        $map = [
            "auth" => AuthMiddleware::class,
            "guest" => GuestMiddleware::class,
        ];

        if (str_starts_with($name, "permission:")) {
            $permission = substr($name, strlen("permission:"));
            return new PermissionMiddleware($permission);
        }

        $class = $map[$name] ?? $name;

        if (!class_exists($class)) {
            throw new RuntimeException("Middleware not found: {$name}");
        }

        $instance = new $class();

        if (!$instance instanceof Middleware) {
            throw new RuntimeException("Invalid middleware: {$class}");
        }

        return $instance;
    }
}
