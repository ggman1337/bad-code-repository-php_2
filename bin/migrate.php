<?php

declare(strict_types=1);

$container = require __DIR__ . '/../bootstrap.php';

/** @var PDO $pdo */
$pdo = $container->make(PDO::class);

$migrator = new App\Support\DatabaseMigrator();
$migrator->migrate($pdo);

echo "Database migrated successfully." . PHP_EOL;
