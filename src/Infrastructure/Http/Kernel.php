<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Security\Authenticator;
use App\Support\Exceptions\HttpException;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;
use Throwable;

class Kernel
{
    private bool $bootstrapped = false;

    public function __construct(
        private Router $router,
        private Authenticator $authenticator
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $this->authenticator->attachUser($request);
            $this->registerRoutes();
            return $this->router->dispatch($request);
        } catch (ValidationException $exception) {
            return Response::json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => $exception->getMessage(),
                    'details' => $exception->payload()['errors'] ?? [],
                ],
            ], 400);
        } catch (HttpException $exception) {
            return Response::json([
                'error' => [
                    'code' => strtoupper(str_replace(' ', '_', $exception->getMessage())) ?: 'HTTP_ERROR',
                    'message' => $exception->getMessage(),
                    'details' => $exception->payload(),
                ],
            ], $exception->statusCode());
        } catch (Throwable $exception) {
            return Response::json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error',
                ],
            ], 500);
        }
    }

    private function registerRoutes(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        $this->bootstrapped = true;

        $this->router->add('POST', '/auth/login', [\App\Http\Controller\AuthController::class, 'login'], false);
        $this->router->add('GET', '/auth/debug', [\App\Http\Controller\AuthController::class, 'debug'], false);

        $this->router->add('GET', '/users', [\App\Http\Controller\UserController::class, 'index'], true, ['admin']);
        $this->router->add('POST', '/users', [\App\Http\Controller\UserController::class, 'store'], true, ['admin']);
        $this->router->add('PUT', '/users/:id', [\App\Http\Controller\UserController::class, 'update'], true, ['admin']);
        $this->router->add('DELETE', '/users/:id', [\App\Http\Controller\UserController::class, 'destroy'], true, ['admin']);

        $this->router->add('GET', '/vehicles', [\App\Http\Controller\VehicleController::class, 'index']);
        $this->router->add('POST', '/vehicles', [\App\Http\Controller\VehicleController::class, 'store'], true, ['admin']);
        $this->router->add('PUT', '/vehicles/:id', [\App\Http\Controller\VehicleController::class, 'update'], true, ['admin']);
        $this->router->add('DELETE', '/vehicles/:id', [\App\Http\Controller\VehicleController::class, 'destroy'], true, ['admin']);

        $this->router->add('GET', '/products', [\App\Http\Controller\ProductController::class, 'index']);
        $this->router->add('POST', '/products', [\App\Http\Controller\ProductController::class, 'store'], true, ['admin']);
        $this->router->add('PUT', '/products/:id', [\App\Http\Controller\ProductController::class, 'update'], true, ['admin']);
        $this->router->add('DELETE', '/products/:id', [\App\Http\Controller\ProductController::class, 'destroy'], true, ['admin']);

        $this->router->add('GET', '/deliveries', [\App\Http\Controller\DeliveryController::class, 'index'], true, ['manager']);
        $this->router->add('POST', '/deliveries', [\App\Http\Controller\DeliveryController::class, 'store'], true, ['manager']);
        $this->router->add('POST', '/deliveries/generate', [\App\Http\Controller\DeliveryController::class, 'generate'], true, ['manager']);
        $this->router->add('GET', '/deliveries/:id', [\App\Http\Controller\DeliveryController::class, 'show']);
        $this->router->add('PUT', '/deliveries/:id', [\App\Http\Controller\DeliveryController::class, 'update'], true, ['manager']);
        $this->router->add('DELETE', '/deliveries/:id', [\App\Http\Controller\DeliveryController::class, 'destroy'], true, ['manager']);

        $this->router->add('GET', '/courier/deliveries', [\App\Http\Controller\CourierController::class, 'index'], true, ['courier']);
        $this->router->add('GET', '/courier/deliveries/:id', [\App\Http\Controller\CourierController::class, 'show'], true, ['courier']);

        $this->router->add('POST', '/routes/calculate', [\App\Http\Controller\RouteController::class, 'calculate']);
    }
}
