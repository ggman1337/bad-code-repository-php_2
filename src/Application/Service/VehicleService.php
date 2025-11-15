<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\Repository\DeliveryRepository;
use App\Infrastructure\Repository\VehicleRepository;
use App\Support\Exceptions\NotFoundException;
use App\Support\Exceptions\ValidationException;

class VehicleService
{
    public function __construct(
        private VehicleRepository $vehicles,
        private DeliveryRepository $deliveries
    ) {
    }

    public function list(): array
    {
        return array_map([$this, 'transform'], $this->vehicles->findAll());
    }

    public function create(array $payload): array
    {
        $data = $this->validatePayload($payload);
        if ($this->vehicles->findByLicensePlate($data['license_plate'])) {
            throw new ValidationException(['license_plate' => 'Машина с таким номером уже существует']);
        }

        $vehicle = $this->vehicles->create($data);
        return $this->transform($vehicle);
    }

    public function update(int $id, array $payload): array
    {
        $vehicle = $this->vehicles->findById($id);
        if (!$vehicle) {
            throw new NotFoundException('Vehicle not found');
        }

        $data = $this->validatePayload($payload);
        if ($data['license_plate'] !== $vehicle['license_plate'] && $this->vehicles->findByLicensePlate($data['license_plate'])) {
            throw new ValidationException(['license_plate' => 'Машина с таким номером уже существует']);
        }

        $updated = $this->vehicles->update($id, $data);
        return $this->transform($updated);
    }

    public function delete(int $id): void
    {
        $vehicle = $this->vehicles->findById($id);
        if (!$vehicle) {
            throw new NotFoundException('Vehicle not found');
        }

        $deliveries = $this->deliveries->findByVehicle($id);
        $hasActive = array_filter($deliveries, function (array $delivery): bool {
            return in_array($delivery['status'], ['planned', 'in_progress'], true);
        });

        if ($hasActive) {
            throw new ValidationException([
                'id' => 'Нельзя удалить машину с активными доставками',
            ]);
        }

        $this->vehicles->delete($id);
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];
        $brand = trim((string) ($payload['brand'] ?? ''));
        $license = trim((string) ($payload['license_plate'] ?? ''));
        $maxWeight = (float) ($payload['max_weight'] ?? 0);
        $maxVolume = (float) ($payload['max_volume'] ?? 0);

        if ($brand === '') {
            $errors['brand'] = 'Марка обязательна';
        }
        if ($license === '') {
            $errors['license_plate'] = 'Номер обязателен';
        }
        if ($maxWeight <= 0) {
            $errors['max_weight'] = 'Вес должен быть положительным';
        }
        if ($maxVolume <= 0) {
            $errors['max_volume'] = 'Объем должен быть положительным';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return [
            'brand' => $brand,
            'license_plate' => $license,
            'max_weight' => $maxWeight,
            'max_volume' => $maxVolume,
        ];
    }

    private function transform(array $vehicle): array
    {
        return [
            'id' => (int) $vehicle['id'],
            'brand' => $vehicle['brand'],
            'license_plate' => $vehicle['license_plate'],
            'max_weight' => (float) $vehicle['max_weight'],
            'max_volume' => (float) $vehicle['max_volume'],
        ];
    }
}
