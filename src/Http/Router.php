<?php

declare(strict_types=1);

namespace PosMyAdmin\Http;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $cleanPath = '/' . trim($path, '/');
        $this->routes[$method][$cleanPath] = $handler;
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $uri = $request->uri();

        $handler = $this->routes[$method][$uri] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo '<h1>404 Not Found</h1><p>The requested route does not exist.</p>';
            exit;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            return $controller->$action($request);
        }

        return $handler($request);
    }
}
