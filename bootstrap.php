<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $path = BASE_PATH . '/src/' . str_replace('App\\', '', $class);
        $path = str_replace('\\', '/', $path) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

$config = [
    'app' => require BASE_PATH . '/config/app.php',
    'database' => require BASE_PATH . '/config/database.php',
];

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

$container = new App\Support\Container();
$container->instance(App\Support\Container::class, $container);
$container->instance(App\Support\Config::class, new App\Support\Config($config));
$container->instance('config', $container->make(App\Support\Config::class));

$container->singleton(PDO::class, function (App\Support\Container $container): PDO {
    $config = $container->make(App\Support\Config::class);
    $database = $config->get('database.database');
    $directory = dirname($database);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $pdo = new PDO('sqlite:' . $database);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
});

$container->singleton(App\Infrastructure\Security\PasswordHasher::class, function (): App\Infrastructure\Security\PasswordHasher {
    return new App\Infrastructure\Security\PasswordHasher();
});

$container->singleton(App\Infrastructure\Security\JwtManager::class, function (App\Support\Container $container): App\Infrastructure\Security\JwtManager {
    return new App\Infrastructure\Security\JwtManager(
        $container->make(App\Support\Config::class)
    );
});

return $container;
