<?php

declare(strict_types=1);

namespace App\Support;

class Request
{
    private ?array $user = null;
    private array $routeParams = [];

    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $body,
        private array $headers
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/';

        $rawBody = file_get_contents('php://input');
        $decoded = json_decode($rawBody ?: '', true);
        $body = is_array($decoded) ? $decoded : [];

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            }
        }

        return new self($method, $path, $_GET ?? [], $body, $headers);
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->query;
    }

    public function body(): array
    {
        return $this->body;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }

    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }
}
