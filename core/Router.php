<?php

class Router
{
    private $routes = [];

    public function get($path, $handler)
    {
        $this->routes["GET"][] = [$path, $handler];
    }

    public function post($path, $handler)
    {
        $this->routes["POST"][] = [$path, $handler];
    }

    public function dispatch($uri, $method)
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes[$method] ?? [] as $route) {
            [$path, $handler] = $route;

            $pattern = $this->convertToRegex($path);

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // remove full match

                return $this->callHandler($handler, $matches);
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    private function convertToRegex($path)
    {
        return "#^" . preg_replace("#\{([\w]+)\}#", "([\w-]+)", $path) . "$#";
    }

    private function callHandler($handler, $params, $middlewares = [])
    {
        [$controller, $method] = explode("@", $handler);

        require_once "../app/Controllers/{$controller}.php";
        $controllerInstance = new $controller();

        $request = $_REQUEST;

        $final = function () use ($controllerInstance, $method, $params) {
            return call_user_func_array(
                [$controllerInstance, $method],
                $params,
            );
        };

        if (!empty($middlewares)) {
            $manager = new MiddlewareManager();

            foreach ($middlewares as $mw) {
                $manager->add($mw);
            }

            return $manager->run($request, $final);
        }

        return $final();
    }
}
