<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function singleton(string $id, Closure $factory): void
    {
        $this->bindings[$id] = function (self $container) use ($factory, $id) {
            if (!array_key_exists($id, $this->instances)) {
                $this->instances[$id] = $factory($container);
            }

            return $this->instances[$id];
        };
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function make(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->bindings)) {
            return $this->bindings[$id]($this);
        }

        if (!class_exists($id)) {
            throw new RuntimeException("Unable to resolve dependency: {$id}");
        }

        $reflection = new ReflectionClass($id);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class {$id} is not instantiable");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            $instance = new $id();
            $this->instances[$id] = $instance;
            return $instance;
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                sprintf('Unable to resolve primitive dependency "%s" for class "%s"', $parameter->getName(), $id)
            );
        }

        $instance = $reflection->newInstanceArgs($dependencies);
        $this->instances[$id] = $instance;
        return $instance;
    }
}
