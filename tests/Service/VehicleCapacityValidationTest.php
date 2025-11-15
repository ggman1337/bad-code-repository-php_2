<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Support\Exceptions\ValidationException;
use Tests\TestCase;

class VehicleCapacityValidationTest extends TestCase
{

    private function smallVehicle(): array
    {
        return $this->createVehicleFixture([
            'brand' => 'Маленький грузовик',
            'license_plate' => 'SM' . random_int(100, 999),
            'max_weight' => 1000,
            'max_volume' => 10,
        ]);
    }

    private function heavyProduct(): array
    {
        return $this->createProductFixture([
            'name' => 'Тяжелый товар',
            'weight' => 600,
            'length' => 100,
            'width' => 100,
            'height' => 100,
        ]);
    }

    private function bulkyProduct(): array
    {
        return $this->createProductFixture([
            'name' => 'Объемный товар',
            'weight' => 10,
            'length' => 200,
            'width' => 200,
            'height' => 200,
        ]);
    }

    private function createDelivery(array $payload): array
    {
        return $this->deliveryService->create($payload, $this->managerUser);
    }

    public function testDeliverySucceedsWhenVehicleHasSufficientCapacity(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->createProductFixture();
        $payload = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 10,
                ]],
            ]],
        ]);
        $result = $this->createDelivery($payload);
        $this->assertNotEmpty($result['delivery_points']);
    }

    public function testDeliveryFailsWhenExceedingVehicleWeightCapacity(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->heavyProduct();
        $payload = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 3,
                ]],
            ]],
        ]);
        $this->expectException(ValidationException::class);
        $this->createDelivery($payload);
    }

    public function testDeliveryFailsWhenExceedingVehicleVolumeCapacity(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->bulkyProduct();
        $payload = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 2,
                ]],
            ]],
        ]);
        $this->expectException(ValidationException::class);
        $this->createDelivery($payload);
    }

    public function testDeliveryFailsWhenCombinedWithExistingDeliveriesExceedCapacity(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->heavyProduct();
        $date = (new \DateTimeImmutable('today +5 days'))->format('Y-m-d');

        $first = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '09:00',
            'time_end' => '12:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);
        $this->createDelivery($first);

        $second = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '10:00',
            'time_end' => '13:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7600,
                'longitude' => 37.6200,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);

        $this->expectException(ValidationException::class);
        $this->createDelivery($second);
    }

    public function testDeliverySucceedsWhenTimePeriodsDoNotOverlap(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->heavyProduct();
        $date = (new \DateTimeImmutable('today +5 days'))->format('Y-m-d');

        $first = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '09:00',
            'time_end' => '12:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);
        $this->createDelivery($first);

        $second = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '13:00',
            'time_end' => '16:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7600,
                'longitude' => 37.6200,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);
        $result = $this->createDelivery($second);
        $this->assertSame('planned', $result['status']);
    }

    public function testDeliveryFailsWhenTimePeriodsOverlap(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->heavyProduct();
        $date = (new \DateTimeImmutable('today +5 days'))->format('Y-m-d');

        $first = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '09:00',
            'time_end' => '13:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);
        $this->createDelivery($first);

        $second = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '12:00',
            'time_end' => '16:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7600,
                'longitude' => 37.6200,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);

        $this->expectException(ValidationException::class);
        $this->createDelivery($second);
    }

    public function testCompletedDeliveriesDoNotAffectCapacityValidation(): void
    {
        $vehicle = $this->smallVehicle();
        $product = $this->heavyProduct();
        $date = (new \DateTimeImmutable('today +5 days'))->format('Y-m-d');

        $first = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '09:00',
            'time_end' => '12:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);
        $delivery = $this->createDelivery($first);
        $this->deliveryRepository->update($delivery['id'], ['status' => 'completed']);

        $second = $this->buildDeliveryPayload([
            'vehicle_id' => $vehicle['id'],
            'delivery_date' => $date,
            'time_start' => '10:00',
            'time_end' => '13:00',
            'points' => [[
                'sequence' => 1,
                'latitude' => 55.7600,
                'longitude' => 37.6200,
                'products' => [[
                    'product_id' => $product['id'],
                    'quantity' => 1,
                ]],
            ]],
        ]);
        $result = $this->createDelivery($second);
        $this->assertSame('planned', $result['status']);
    }
}
