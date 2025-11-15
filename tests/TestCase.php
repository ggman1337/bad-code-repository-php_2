<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Service\DeliveryService;
use App\Application\Service\OpenStreetMapService;
use App\Infrastructure\Http\Kernel;
use App\Infrastructure\Repository\DeliveryRepository;
use App\Infrastructure\Repository\ProductRepository;
use App\Infrastructure\Repository\UserRepository;
use App\Infrastructure\Repository\VehicleRepository;
use App\Infrastructure\Security\JwtManager;
use App\Infrastructure\Security\PasswordHasher;
use App\Support\Container;
use App\Support\DatabaseMigrator;
use App\Support\DatabaseSeeder;
use App\Support\Request;
use App\Support\Response;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $projectRoot;
    protected Container $container;
    protected Kernel $kernel;
    protected UserRepository $userRepository;
    protected VehicleRepository $vehicleRepository;
    protected ProductRepository $productRepository;
    protected DeliveryRepository $deliveryRepository;
    protected JwtManager $jwtManager;
    protected PasswordHasher $passwordHasher;
    protected DeliveryService $deliveryService;
    protected OpenStreetMapService $openStreetMapService;

    protected array $adminUser;
    protected array $managerUser;
    protected array $courierUser;
    protected string $adminToken;
    protected string $managerToken;
    protected string $courierToken;

    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__);
        $this->databasePath = $this->projectRoot . '/storage/test_' . uniqid('', true) . '.sqlite';
        putenv('APP_DATABASE=' . $this->databasePath);
        @unlink($this->databasePath);

        $this->container = require $this->projectRoot . '/bootstrap.php';

        /** @var PDO $pdo */
        $pdo = $this->container->make(PDO::class);
        (new DatabaseMigrator())->migrate($pdo);

        $seeder = new DatabaseSeeder(
            $this->container->make(UserRepository::class),
            $this->container->make(\App\Infrastructure\Security\PasswordHasher::class),
            $this->container->make(VehicleRepository::class),
            $this->container->make(ProductRepository::class)
        );
        $seeder->seed();

        $this->kernel = $this->container->make(Kernel::class);
        $this->userRepository = $this->container->make(UserRepository::class);
        $this->vehicleRepository = $this->container->make(VehicleRepository::class);
        $this->productRepository = $this->container->make(ProductRepository::class);
        $this->deliveryRepository = $this->container->make(DeliveryRepository::class);
        $this->jwtManager = $this->container->make(JwtManager::class);
        $this->passwordHasher = $this->container->make(PasswordHasher::class);
        $this->deliveryService = $this->container->make(DeliveryService::class);
        $this->openStreetMapService = $this->container->make(OpenStreetMapService::class);

        $this->adminUser = $this->userRepository->findByLogin('admin');
        $this->managerUser = $this->userRepository->findByLogin('manager');
        $this->courierUser = $this->userRepository->findByLogin('courier');

        $this->adminToken = $this->jwtManager->issueToken([
            'sub' => (int) $this->adminUser['id'],
            'login' => $this->adminUser['login'],
            'role' => $this->adminUser['role'],
        ]);
        $this->managerToken = $this->jwtManager->issueToken([
            'sub' => (int) $this->managerUser['id'],
            'login' => $this->managerUser['login'],
            'role' => $this->managerUser['role'],
        ]);
        $this->courierToken = $this->jwtManager->issueToken([
            'sub' => (int) $this->courierUser['id'],
            'login' => $this->courierUser['login'],
            'role' => $this->courierUser['role'],
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (isset($this->databasePath) && file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }
    }

    protected function request(
        string $method,
        string $path,
        array $body = [],
        ?string $token = null,
        array $query = []
    ): Response {
        $headers = [];
        if ($token) {
            $headers['authorization'] = 'Bearer ' . $token;
        }

        $request = new Request($method, $path, $query, $body, array_change_key_case($headers, CASE_LOWER));
        return $this->kernel->handle($request);
    }

    protected function getJson(string $path, ?string $token = null, array $query = []): Response
    {
        return $this->request('GET', $path, [], $token, $query);
    }

    protected function postJson(string $path, array $body, ?string $token = null): Response
    {
        return $this->request('POST', $path, $body, $token);
    }

    protected function putJson(string $path, array $body, ?string $token = null): Response
    {
        return $this->request('PUT', $path, $body, $token);
    }

    protected function deleteJson(string $path, ?string $token = null): Response
    {
        return $this->request('DELETE', $path, [], $token);
    }

    protected function assertSuccess(Response $response, int $status = 200): array
    {
        $this->assertSame($status, $response->status());
        $body = $response->body();
        $this->assertIsArray($body);
        return $body;
    }

    protected function assertError(Response $response, int $status): array
    {
        $this->assertSame($status, $response->status());
        $body = $response->body();
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
        return $body['error'];
    }

    protected function createVehicleFixture(array $attributes = []): array
    {
        return $this->vehicleRepository->create([
            'brand' => $attributes['brand'] ?? 'Ford Transit',
            'license_plate' => $attributes['license_plate'] ?? ('PLT' . strtoupper(bin2hex(random_bytes(3)))),
            'max_weight' => $attributes['max_weight'] ?? 1500,
            'max_volume' => $attributes['max_volume'] ?? 18,
        ]);
    }

    protected function createProductFixture(array $attributes = []): array
    {
        return $this->productRepository->create([
            'name' => $attributes['name'] ?? 'Тестовый товар',
            'weight' => $attributes['weight'] ?? 1.5,
            'length' => $attributes['length'] ?? 10,
            'width' => $attributes['width'] ?? 10,
            'height' => $attributes['height'] ?? 10,
        ]);
    }

    protected function createUserFixture(array $attributes = []): array
    {
        return $this->userRepository->create([
            'login' => $attributes['login'] ?? 'user' . random_int(100, 999),
            'password_hash' => $this->passwordHasher->hash($attributes['password'] ?? 'password123'),
            'name' => $attributes['name'] ?? 'Test User',
            'role' => $attributes['role'] ?? 'courier',
            'created_at' => date(DATE_ATOM),
        ]);
    }

    protected function buildDeliveryPayload(array $overrides = []): array
    {
        $vehicleId = $overrides['vehicle_id'] ?? ($this->vehicleRepository->findAll()[0]['id'] ?? $this->createVehicleFixture()['id']);
        $productId = $overrides['product_id'] ?? ($this->productRepository->findAll()[0]['id'] ?? $this->createProductFixture()['id']);
        $courierId = $overrides['courier_id'] ?? (int) $this->courierUser['id'];

        $payload = [
            'courier_id' => $courierId,
            'vehicle_id' => $vehicleId,
            'delivery_date' => $overrides['delivery_date'] ?? (new \DateTimeImmutable('today +5 days'))->format('Y-m-d'),
            'time_start' => $overrides['time_start'] ?? '09:00',
            'time_end' => $overrides['time_end'] ?? '18:00',
            'points' => $overrides['points'] ?? [
                [
                    'sequence' => 1,
                    'latitude' => 55.7558,
                    'longitude' => 37.6176,
                    'products' => [
                        [
                            'product_id' => $productId,
                            'quantity' => $overrides['quantity'] ?? 2,
                        ],
                    ],
                ],
            ],
        ];

        unset($overrides['vehicle_id'], $overrides['product_id'], $overrides['courier_id'], $overrides['quantity']);

        return array_replace_recursive($payload, $overrides);
    }

    protected function createDeliveryFixture(array $overrides = []): array
    {
        $payload = $this->buildDeliveryPayload($overrides);
        return $this->deliveryService->create($payload, $this->managerUser);
    }
}
