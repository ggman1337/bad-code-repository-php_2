<?php

declare(strict_types=1);

namespace Tests\Controller;

use Tests\TestCase;

class CourierControllerTest extends TestCase
{
    private function createDeliveryForCourier(array $overrides = []): array
    {
        return $this->createDeliveryFixture($overrides + ['courier_id' => (int) $this->courierUser['id']]);
    }

    public function testCourierGetsOwnDeliveries(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries', $this->courierToken);
        $body = $this->assertSuccess($response);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['data']);
        $first = $body['data'][0];
        $this->assertArrayHasKey('delivery_number', $first);
        $this->assertArrayHasKey('points_count', $first);
        $this->assertArrayHasKey('products_count', $first);
    }

    public function testCourierDeliveriesAsAdminForbidden(): void
    {
        $response = $this->getJson('/courier/deliveries', $this->adminToken);
        $this->assertError($response, 403);
    }

    public function testCourierDeliveriesAsManagerForbidden(): void
    {
        $response = $this->getJson('/courier/deliveries', $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testCourierDeliveriesWithoutAuthForbidden(): void
    {
        $response = $this->getJson('/courier/deliveries');
        $this->assertError($response, 403);
    }

    public function testCourierDeliveriesWithDateFilter(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries', $this->courierToken, [
            'date' => $delivery['delivery_date'],
        ]);
        $body = $this->assertSuccess($response);
        $this->assertCount(1, $body['data']);
    }

    public function testCourierDeliveriesWithNonMatchingDateReturnsEmpty(): void
    {
        $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries', $this->courierToken, [
            'date' => (new \DateTimeImmutable('today +10 days'))->format('Y-m-d'),
        ]);
        $body = $this->assertSuccess($response);
        $this->assertCount(0, $body['data']);
    }

    public function testCourierDeliveriesWithStatusFilter(): void
    {
        $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries', $this->courierToken, ['status' => 'planned']);
        $body = $this->assertSuccess($response);
        $this->assertNotEmpty($body['data']);
        $this->assertSame('planned', $body['data'][0]['status']);
    }

    public function testCourierDeliveriesWithDateRange(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $date = new \DateTimeImmutable($delivery['delivery_date']);
        $response = $this->getJson('/courier/deliveries', $this->courierToken, [
            'date_from' => $date->modify('-1 day')->format('Y-m-d'),
            'date_to' => $date->modify('+1 day')->format('Y-m-d'),
        ]);
        $body = $this->assertSuccess($response);
        $this->assertCount(1, $body['data']);
    }

    public function testCourierDoesNotSeeOtherCouriersDeliveries(): void
    {
        $otherCourier = $this->createUserFixture(['role' => 'courier', 'login' => 'othercourier']);
        $this->createDeliveryFixture(['courier_id' => (int) $otherCourier['id']]);
        $response = $this->getJson('/courier/deliveries', $this->courierToken);
        $body = $this->assertSuccess($response);
        $this->assertCount(0, $body['data']);
    }

    public function testCourierCanGetDeliveryDetails(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries/' . $delivery['id'], $this->courierToken);
        $body = $this->assertSuccess($response);
        $this->assertSame($delivery['id'], $body['data']['id']);
        $this->assertSame((int) $this->courierUser['id'], $body['data']['courier']['id']);
        $this->assertIsArray($body['data']['delivery_points']);
    }

    public function testCourierCannotAccessOthersDelivery(): void
    {
        $otherCourier = $this->createUserFixture(['role' => 'courier', 'login' => 'othercourier2']);
        $delivery = $this->createDeliveryFixture(['courier_id' => (int) $otherCourier['id']]);
        $response = $this->getJson('/courier/deliveries/' . $delivery['id'], $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testGettingNonExistentDeliveryReturnsBadRequest(): void
    {
        $response = $this->getJson('/courier/deliveries/9999', $this->courierToken);
        $this->assertError($response, 404);
    }

    public function testCourierDeliveryAsAdminForbidden(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries/' . $delivery['id'], $this->adminToken);
        $this->assertError($response, 403);
    }

    public function testCourierDeliveryAsManagerForbidden(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $response = $this->getJson('/courier/deliveries/' . $delivery['id'], $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testCourierSeesVehicleInformation(): void
    {
        $vehicle = $this->createVehicleFixture(['brand' => 'Ford Transit', 'license_plate' => 'A123BV']);
        $this->createDeliveryFixture(['courier_id' => (int) $this->courierUser['id'], 'vehicle_id' => (int) $vehicle['id']]);
        $response = $this->getJson('/courier/deliveries', $this->courierToken);
        $body = $this->assertSuccess($response);
        $this->assertSame('Ford Transit', $body['data'][0]['vehicle']['brand']);
        $this->assertSame('A123BV', $body['data'][0]['vehicle']['license_plate']);
    }

    public function testCourierSeesDeliveryWithoutVehicleAssigned(): void
    {
        $delivery = $this->createDeliveryForCourier();
        $this->deliveryRepository->update($delivery['id'], ['vehicle_id' => null]);
        $response = $this->getJson('/courier/deliveries', $this->courierToken);
        $body = $this->assertSuccess($response);
        $this->assertSame('Не назначена', $body['data'][0]['vehicle']['brand']);
        $this->assertSame('', $body['data'][0]['vehicle']['license_plate']);
    }
}
