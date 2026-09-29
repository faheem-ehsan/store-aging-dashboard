<?php
namespace App\Core;

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = app_config('app')['base_url'] ?? '';
        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = $this->normalize($path);

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not Found']);
            return;
        }

        [$class, $action] = $handler;
        (new $class())->{$action}();
    }

    private function normalize(string $path): string
    {
        if ($path === '') return '/';
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
