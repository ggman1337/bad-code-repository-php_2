<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\ValueObject\DeliveryStatus;
use App\Infrastructure\Repository\DeliveryRepository;
use App\Support\Exceptions\ForbiddenException;
use App\Support\Exceptions\ValidationException;
use DateTimeImmutable;

class CourierService
{
    public function __construct(
        private DeliveryRepository $deliveries,
        private DeliveryService $deliveryService
    ) {
    }

    public function listForCourier(array $filters, array $currentUser): array
    {
        $normalizedFilters = [
            'date' => $this->parseDate($filters['date'] ?? null),
            'status' => $this->parseStatus($filters['status'] ?? null),
            'date_from' => $this->parseDate($filters['date_from'] ?? null),
            'date_to' => $this->parseDate($filters['date_to'] ?? null),
        ];

        $rows = $this->deliveries->findByFilters([
            'courier_id' => (int) $currentUser['id'],
            'date' => $normalizedFilters['date'],
            'status' => $normalizedFilters['status'],
            'date_from' => $normalizedFilters['date_from'],
            'date_to' => $normalizedFilters['date_to'],
        ]);
        $detailed = $this->deliveryService->presentDeliveries($rows);

        return array_map([$this, 'summarize'], $detailed);
    }

    public function getCourierDelivery(int $id, array $currentUser): array
    {
        $delivery = $this->deliveryService->get($id);
        $courierId = $delivery['courier']['id'] ?? null;
        if ($courierId !== (int) $currentUser['id']) {
            throw new ForbiddenException('Доставка принадлежит другому курьеру');
        }
        return $delivery;
    }

    private function summarize(array $delivery): array
    {
        $pointsCount = count($delivery['delivery_points']);
        $productsCount = 0;
        foreach ($delivery['delivery_points'] as $point) {
            foreach ($point['products'] as $product) {
                $productsCount += $product['quantity'];
            }
        }

        $vehicleInfo = $delivery['vehicle']
            ? [
                'brand' => $delivery['vehicle']['brand'],
                'license_plate' => $delivery['vehicle']['license_plate'],
            ]
            : [
                'brand' => 'Не назначена',
                'license_plate' => '',
            ];

        return [
            'id' => $delivery['id'],
            'delivery_number' => $delivery['delivery_number'],
            'delivery_date' => $delivery['delivery_date'],
            'time_start' => $delivery['time_start'],
            'time_end' => $delivery['time_end'],
            'status' => $delivery['status'],
            'vehicle' => $vehicleInfo,
            'points_count' => $pointsCount,
            'products_count' => $productsCount,
            'total_weight' => $delivery['total_weight'],
        ];
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date) {
            throw new ValidationException(['date' => 'Некорректный формат даты']);
        }
        return $date->format('Y-m-d');
    }

    private function parseStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }
        if (!DeliveryStatus::isValid($status)) {
            throw new ValidationException(['status' => 'Некорректный статус']);
        }
        return $status;
    }
}
