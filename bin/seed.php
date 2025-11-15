<?php

declare(strict_types=1);

$container = require __DIR__ . '/../bootstrap.php';

$seeder = new App\Support\DatabaseSeeder(
    $container->make(App\Infrastructure\Repository\UserRepository::class),
    $container->make(App\Infrastructure\Security\PasswordHasher::class),
    $container->make(App\Infrastructure\Repository\VehicleRepository::class),
    $container->make(App\Infrastructure\Repository\ProductRepository::class),
);
$seeder->seed();

echo "Seed data applied." . PHP_EOL;
