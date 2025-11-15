<?php

declare(strict_types=1);

$container = require __DIR__ . '/../bootstrap.php';

use App\Infrastructure\Http\Kernel;
use App\Support\Request;

/** @var Kernel $kernel */
$kernel = $container->make(Kernel::class);
$request = Request::fromGlobals();
$response = $kernel->handle($request);
$response->send();
