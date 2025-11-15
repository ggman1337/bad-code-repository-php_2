<?php

declare(strict_types=1);

namespace Tests\Controller;

use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Mercedes Sprinter',
            'license_plate' => 'V' . random_int(1000, 9999),
            'max_weight' => 1500,
            'max_volume' => 20,
        ], $overrides);
    }

    public function testGetAllVehiclesShouldReturnList(): void
    {
        $this->createVehicleFixture();
        $response = $this->getJson('/vehicles', $this->adminToken);
        $body = $this->assertSuccess($response);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['data']);
        $this->assertArrayHasKey('brand', $body['data'][0]);
    }

    public function testGetAllVehiclesWithoutAuthShouldReturnForbidden(): void
    {
        $response = $this->getJson('/vehicles');
        $this->assertError($response, 403);
    }

    public function testCreateVehicleAsAdminShouldSucceed(): void
    {
        $payload = $this->vehiclePayload(['license_plate' => 'A123BC']);
        $response = $this->postJson('/vehicles', $payload, $this->adminToken);
        $body = $this->assertSuccess($response, 201);
        $this->assertSame('Mercedes Sprinter', $body['data']['brand']);
        $this->assertSame('A123BC', $body['data']['license_plate']);
    }

    public function testCreateVehicleAsManagerShouldReturnForbidden(): void
    {
        $response = $this->postJson('/vehicles', $this->vehiclePayload(), $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testCreateVehicleAsCourierShouldReturnForbidden(): void
    {
        $response = $this->postJson('/vehicles', $this->vehiclePayload(), $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testCreateVehicleWithDuplicateLicensePlateShouldReturnBadRequest(): void
    {
        $vehicle = $this->createVehicleFixture(['license_plate' => 'DUP123']);
        $response = $this->postJson('/vehicles', $this->vehiclePayload(['license_plate' => 'DUP123']), $this->adminToken);
        $this->assertError($response, 400);
    }

    public function testCreateVehicleWithInvalidDataShouldReturnBadRequest(): void
    {
        $payload = [
            'brand' => '',
            'license_plate' => '',
            'max_weight' => -100,
            'max_volume' => -10,
        ];
        $response = $this->postJson('/vehicles', $payload, $this->adminToken);
        $this->assertError($response, 400);
    }

    public function testUpdateVehicleAsAdminShouldSucceed(): void
    {
        $vehicle = $this->createVehicleFixture();
        $payload = $this->vehiclePayload([
            'brand' => 'Updated Ford',
            'license_plate' => 'U123XZ',
        ]);
        $response = $this->putJson('/vehicles/' . $vehicle['id'], $payload, $this->adminToken);
        $body = $this->assertSuccess($response);
        $this->assertSame('Updated Ford', $body['data']['brand']);
        $this->assertSame('U123XZ', $body['data']['license_plate']);
    }

    public function testUpdateVehicleAsManagerShouldReturnForbidden(): void
    {
        $vehicle = $this->createVehicleFixture();
        $response = $this->putJson('/vehicles/' . $vehicle['id'], $this->vehiclePayload(), $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testUpdateNonExistentVehicleShouldReturnBadRequest(): void
    {
        $response = $this->putJson('/vehicles/9999', $this->vehiclePayload(), $this->adminToken);
        $this->assertError($response, 404);
    }

    public function testUpdateVehicleWithDuplicateLicensePlateShouldReturnBadRequest(): void
    {
        $vehicleA = $this->createVehicleFixture(['license_plate' => 'AAA111']);
        $vehicleB = $this->createVehicleFixture(['license_plate' => 'BBB222']);
        $response = $this->putJson('/vehicles/' . $vehicleB['id'], $this->vehiclePayload(['license_plate' => 'AAA111']), $this->adminToken);
        $this->assertError($response, 400);
    }

    public function testDeleteVehicleAsAdminShouldSucceed(): void
    {
        $vehicle = $this->createVehicleFixture();
        $response = $this->deleteJson('/vehicles/' . $vehicle['id'], $this->adminToken);
        $this->assertSame(204, $response->status());
    }

    public function testDeleteVehicleAsManagerShouldReturnForbidden(): void
    {
        $vehicle = $this->createVehicleFixture();
        $response = $this->deleteJson('/vehicles/' . $vehicle['id'], $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testDeleteNonExistentVehicleShouldReturnBadRequest(): void
    {
        $response = $this->deleteJson('/vehicles/9999', $this->adminToken);
        $this->assertError($response, 404);
    }
}
