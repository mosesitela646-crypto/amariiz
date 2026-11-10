<?php
declare(strict_types=1);

namespace Core;

final class Router
{
    private array $routes = [];
    public function add(string $method, string $path, callable $handler): void { $this->routes[] = [strtoupper($method), $path, $handler]; }
    public function dispatch(string $method, string $path): never
    {
        $methodAllowed = false;
        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            $pattern = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $routePath) . '$#';
            if (!preg_match($pattern, $path, $matches)) continue;
            $methodAllowed = true;
            if ($routeMethod !== strtoupper($method)) continue;
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler($params);
        }
        Response::error($methodAllowed ? 'Méthode HTTP non autorisée.' : 'Route introuvable.', $methodAllowed ? 405 : 404);
    }
}
