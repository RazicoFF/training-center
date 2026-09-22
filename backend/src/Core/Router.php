<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, paramNames:array<int,string>, handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['pattern'], $request->path(), $matches)) {
                array_shift($matches);
                $params = array_combine($route['paramNames'], $matches);
                $request->setParams($params ?: []);

                return ($route['handler'])($request);
            }
        }

        return null;
    }
}
