<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Support\Exceptions\ValidationException;
use Tests\TestCase;

class RouteTimeValidationTest extends TestCase
{

    public function testOpenStreetMapServiceLongDistanceCalculation(): void
    {
        $distance = $this->openStreetMapService->calculateDistance(55.7558, 37.6176, 59.9311, 30.3609);
        $this->assertEqualsWithDelta(632, $distance, 5);
    }

    public function testOpenStreetMapServiceShortDistanceCalculation(): void
    {
        $distance = $this->openStreetMapService->calculateDistance(55.7558, 37.6176, 55.7600, 37.6200);
        $this->assertEqualsWithDelta(0.5, $distance, 0.2);
    }

    public function testDeliveryValidationPassesForShortRouteWithSufficientTime(): void
    {
        $payload = $this->buildDeliveryPayload([
            'time_start' => '09:00',
            'time_end' => '18:00',
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
                    'latitude' => 55.7600,
                    'longitude' => 37.6200,
                    'products' => [
                        ['product_id' => $this->productRepository->findAll()[0]['id'], 'quantity' => 1],
                    ],
                ],
            ],
        ]);

        $result = $this->deliveryService->create($payload, $this->managerUser);
        $this->assertSame('planned', $result['status']);
    }

    public function testDeliveryValidationFailsForLongRouteWithInsufficientTime(): void
    {
        $payload = $this->buildDeliveryPayload([
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

        try {
            $this->deliveryService->create($payload, $this->managerUser);
            $this->fail('Expected validation exception');
        } catch (ValidationException $exception) {
            $errors = $exception->payload()['errors'] ?? [];
            $this->assertArrayHasKey('time', $errors);
        }
    }
}
