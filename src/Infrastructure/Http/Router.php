<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Support\Container;
use App\Support\Exceptions\ForbiddenException;
use App\Support\Exceptions\NotFoundException;
use App\Support\Exceptions\UnauthorizedException;
use App\Support\Request;
use App\Support\Response;

class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:array,auth:bool,roles:array}> */
    private array $routes = [];

    public function __construct(private Container $container)
    {
    }

    public function add(
        string $method,
        string $pattern,
        array $handler,
        bool $requiresAuth = true,
        array $roles = []
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'auth' => $requiresAuth,
            'roles' => $roles,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $params = $this->match($route['pattern'], $request->path());
            if ($params === null) {
                continue;
            }

            if ($route['auth']) {
                $user = $request->user();
                if ($user === null) {
                    throw new ForbiddenException('Authentication required');
                }

                if (!empty($route['roles']) && !in_array($user['role'], $route['roles'], true)) {
                    throw new ForbiddenException('Insufficient permissions');
                }
            }

            $request->setRouteParams($params);

            [$controllerClass, $method] = $route['handler'];
            $controller = $this->container->make($controllerClass);
            $response = $controller->{$method}($request, $params);
            if ($response instanceof Response) {
                return $response;
            }

            return Response::json(['data' => $response]);
        }

        throw new NotFoundException('Endpoint not found');
    }

    private function match(string $pattern, string $path): ?array
    {
        $pattern = '/' . trim($pattern, '/');
        $path = '/' . trim($path, '/');

        $regex = preg_replace('#:([a-zA-Z_][a-zA-Z0-9_]*)#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
