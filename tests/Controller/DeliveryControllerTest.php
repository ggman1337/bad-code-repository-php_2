<?php

declare(strict_types=1);

namespace Tests\Controller;

use Tests\TestCase;

class DeliveryControllerTest extends TestCase
{
    private function deliveryPayload(array $overrides = []): array
    {
        return $this->buildDeliveryPayload($overrides);
    }

    private function postDelivery(array $overrides = [], ?string $token = null)
    {
        return $this->postJson('/deliveries', $this->deliveryPayload($overrides), $token ?? $this->managerToken);
    }

    public function testGetAllDeliveriesAsManagerShouldSucceed(): void
    {
        $this->createDeliveryFixture();
        $response = $this->getJson('/deliveries', $this->managerToken);
        $body = $this->assertSuccess($response);
        $this->assertNotEmpty($body['data']);
    }

    public function testGetAllDeliveriesAsCourierShouldReturnForbidden(): void
    {
        $response = $this->getJson('/deliveries', $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testGetAllDeliveriesAsAdminShouldReturnForbidden(): void
    {
        $response = $this->getJson('/deliveries', $this->adminToken);
        $this->assertError($response, 403);
    }

    public function testGetDeliveriesWithDateFilter(): void
    {
        $delivery = $this->createDeliveryFixture();
        $response = $this->getJson('/deliveries', $this->managerToken, ['date' => $delivery['delivery_date']]);
        $body = $this->assertSuccess($response);
        $this->assertCount(1, $body['data']);
    }

    public function testGetDeliveriesWithCourierFilter(): void
    {
        $this->createDeliveryFixture();
        $response = $this->getJson('/deliveries', $this->managerToken, ['courier_id' => $this->courierUser['id']]);
        $body = $this->assertSuccess($response);
        $this->assertCount(1, $body['data']);
    }

    public function testGetDeliveriesWithStatusFilter(): void
    {
        $this->createDeliveryFixture();
        $response = $this->getJson('/deliveries', $this->managerToken, ['status' => 'planned']);
        $body = $this->assertSuccess($response);
        $this->assertSame('planned', $body['data'][0]['status']);
    }

    public function testCreateDeliveryAsManagerShouldSucceed(): void
    {
        $response = $this->postDelivery();
        $body = $this->assertSuccess($response, 201);
        $this->assertSame((int) $this->courierUser['id'], $body['data']['courier']['id']);
        $this->assertArrayHasKey('delivery_points', $body['data']);
    }

    public function testCreateDeliveryAsCourierShouldReturnForbidden(): void
    {
        $response = $this->postDelivery([], $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testCreateDeliveryWithInvalidCourierRoleShouldReturnBadRequest(): void
    {
        $adminCourier = $this->createUserFixture(['login' => 'fakecourier', 'role' => 'admin']);
        $response = $this->postDelivery(['courier_id' => (int) $adminCourier['id']]);
        $this->assertError($response, 400);
    }

    public function testCreateDeliveryWithPastDateShouldReturnBadRequest(): void
    {
        $response = $this->postDelivery(['delivery_date' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d')]);
        $this->assertError($response, 400);
    }

    public function testCreateDeliveryWithInvalidTimeShouldReturnBadRequest(): void
    {
        $response = $this->postDelivery(['time_start' => '18:00', 'time_end' => '17:00']);
        $this->assertError($response, 400);
    }

    public function testGetDeliveryByIdShouldReturnDetails(): void
    {
        $delivery = $this->createDeliveryFixture();
        $response = $this->getJson('/deliveries/' . $delivery['id'], $this->managerToken);
        $body = $this->assertSuccess($response);
        $this->assertSame($delivery['id'], $body['data']['id']);
        $this->assertArrayHasKey('delivery_points', $body['data']);
    }

    public function testUpdateDeliveryAsManagerShouldSucceedWhenDateFarEnough(): void
    {
        $delivery = $this->createDeliveryFixture();
        $payload = $this->deliveryPayload([
            'delivery_date' => (new \DateTimeImmutable('today +6 days'))->format('Y-m-d'),
            'time_start' => '10:00',
            'time_end' => '19:00',
        ]);
        $response = $this->putJson('/deliveries/' . $delivery['id'], $payload, $this->managerToken);
        $body = $this->assertSuccess($response);
        $this->assertSame('10:00', $body['data']['time_start']);
    }

    public function testUpdateDeliveryLessThanThreeDaysBeforeShouldReturnBadRequest(): void
    {
        $delivery = $this->createDeliveryFixture(['delivery_date' => (new \DateTimeImmutable('today +2 days'))->format('Y-m-d')]);
        $payload = $this->deliveryPayload();
        $response = $this->putJson('/deliveries/' . $delivery['id'], $payload, $this->managerToken);
        $this->assertError($response, 400);
    }

    public function testDeleteDeliveryAsManagerShouldSucceedWhenFarEnough(): void
    {
        $delivery = $this->createDeliveryFixture();
        $response = $this->deleteJson('/deliveries/' . $delivery['id'], $this->managerToken);
        $this->assertSame(204, $response->status());
    }

    public function testDeleteDeliveryLessThanThreeDaysBeforeShouldReturnBadRequest(): void
    {
        $delivery = $this->createDeliveryFixture(['delivery_date' => (new \DateTimeImmutable('today +1 day'))->format('Y-m-d')]);
        $response = $this->deleteJson('/deliveries/' . $delivery['id'], $this->managerToken);
        $this->assertError($response, 400);
    }

    public function testGenerateDeliveriesAsManagerShouldSucceed(): void
    {
        $product = $this->createProductFixture();
        $payload = [
            'delivery_data' => [
                (new \DateTimeImmutable('today +5 days'))->format('Y-m-d') => [
                    [
                        'route' => [
                            [
                                'sequence' => 1,
                                'latitude' => 55.7558,
                                'longitude' => 37.6176,
                                'products' => [],
                            ],
                        ],
                        'products' => [
                            ['product_id' => (int) $product['id'], 'quantity' => 5],
                        ],
                    ],
                ],
            ],
        ];
        $response = $this->postJson('/deliveries/generate', $payload, $this->managerToken);
        $body = $this->assertSuccess($response);
        $this->assertArrayHasKey('data', $body);
        $data = $body['data'];
        $this->assertTrue(array_key_exists('totalGenerated', $data) || array_key_exists('total_generated', $data));
    }

    public function testGenerateDeliveriesAsCourierShouldReturnForbidden(): void
    {
        $payload = ['delivery_data' => []];
        $response = $this->postJson('/deliveries/generate', $payload, $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testCreateDeliveryWithInsufficientTimeWindowShouldReturnBadRequest(): void
    {
        $response = $this->postDelivery([
            'time_start' => '09:00',
            'time_end' => '09:30',
            'points' => [
                [
                    'sequence' => 1,
                    'latitude' => 55.7558,
                    'longitude' => 37.6176,
                    'products' => [
                        ['product_id' => $this->productRepository->findAll()[0]['id'], 'quantity' => 1],
                    ],
                ],
                [
                    'sequence' => 2,
                    'latitude' => 59.9311,
                    'longitude' => 30.3609,
                    'products' => [
                        ['product_id' => $this->productRepository->findAll()[0]['id'], 'quantity' => 1],
                    ],
                ],
            ],
        ]);
        $this->assertError($response, 400);
    }

    public function testUpdateDeliveryWithInsufficientTimeWindowShouldReturnBadRequest(): void
    {
        $delivery = $this->createDeliveryFixture();
        $payload = $this->deliveryPayload([
            'time_start' => '09:00',
            'time_end' => '09:30',
            'points' => [
                [
                    'sequence' => 1,
                    'latitude' => 55.7558,
                    'longitude' => 37.6176,
                    'products' => [
                        ['product_id' => $this->productRepository->findAll()[0]['id'], 'quantity' => 1],
                    ],
                ],
                [
                    'sequence' => 2,
                    'latitude' => 59.9311,
                    'longitude' => 30.3609,
                    'products' => [
                        ['product_id' => $this->productRepository->findAll()[0]['id'], 'quantity' => 1],
                    ],
                ],
            ],
        ]);
        $response = $this->putJson('/deliveries/' . $delivery['id'], $payload, $this->managerToken);
        $this->assertError($response, 400);
    }

    public function testCreateDeliveryWithSufficientTimeWindowShouldSucceed(): void
    {
        $response = $this->postDelivery([
            'time_start' => '08:00',
            'time_end' => '18:00',
        ]);
        $this->assertSuccess($response, 201);
    }

    public function testCreateDeliveryShouldFailWhenVehicleWeightExceeded(): void
    {
        $vehicle = $this->createVehicleFixture(['max_weight' => 100]);
        $product = $this->createProductFixture(['weight' => 200]);
        $payload = $this->deliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [
                    ['product_id' => $product['id'], 'quantity' => 2],
                ],
            ]],
        ]);
        $response = $this->postJson('/deliveries', $payload, $this->managerToken);
        $this->assertError($response, 400);
    }

    public function testCreateDeliveryShouldFailWhenVehicleVolumeExceeded(): void
    {
        $vehicle = $this->createVehicleFixture(['max_volume' => 1]);
        $product = $this->createProductFixture(['length' => 200, 'width' => 200, 'height' => 200]);
        $payload = $this->deliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [
                    ['product_id' => $product['id'], 'quantity' => 2],
                ],
            ]],
        ]);
        $response = $this->postJson('/deliveries', $payload, $this->managerToken);
        $this->assertError($response, 400);
    }
}
