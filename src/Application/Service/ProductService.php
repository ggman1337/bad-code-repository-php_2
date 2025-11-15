<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\Repository\DeliveryRepository;
use App\Infrastructure\Repository\ProductRepository;
use App\Support\Exceptions\NotFoundException;
use App\Support\Exceptions\ValidationException;

class ProductService
{
    public function __construct(
        private ProductRepository $products,
        private DeliveryRepository $deliveries
    ) {
    }

    public function list(): array
    {
        return array_map([$this, 'transform'], $this->products->findAll());
    }

    public function create(array $payload): array
    {
        $data = $this->validatePayload($payload);
        $product = $this->products->create($data);
        return $this->transform($product);
    }

    public function update(int $id, array $payload): array
    {
        $product = $this->products->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $data = $this->validatePayload($payload);
        $updated = $this->products->update($id, $data);
        return $this->transform($updated);
    }

    public function delete(int $id): void
    {
        $product = $this->products->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $deliveries = $this->deliveries->findByProductId($id);
        $hasActive = array_filter($deliveries, fn(array $delivery) => in_array($delivery['status'], ['planned', 'in_progress'], true));
        if ($hasActive) {
            throw new ValidationException(['id' => 'Нельзя удалить товар, участвующий в активных доставках']);
        }

        $this->products->delete($id);
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];
        $name = trim((string) ($payload['name'] ?? ''));
        $weight = isset($payload['weight']) ? (float) $payload['weight'] : 0.0;
        $length = isset($payload['length']) ? (float) $payload['length'] : 0.0;
        $width = isset($payload['width']) ? (float) $payload['width'] : 0.0;
        $height = isset($payload['height']) ? (float) $payload['height'] : 0.0;

        if ($name === '') {
            $errors['name'] = 'Название обязательно';
        }
        if ($weight <= 0) {
            $errors['weight'] = 'Вес должен быть положительным';
        }
        if ($length <= 0) {
            $errors['length'] = 'Длина должна быть положительной';
        }
        if ($width <= 0) {
            $errors['width'] = 'Ширина должна быть положительной';
        }
        if ($height <= 0) {
            $errors['height'] = 'Высота должна быть положительной';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return [
            'name' => $name,
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function transform(array $product): array
    {
        $volume = (float) $product['length'] * (float) $product['width'] * (float) $product['height'] / 1_000_000;
        return [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'weight' => (float) $product['weight'],
            'length' => (float) $product['length'],
            'width' => (float) $product['width'],
            'height' => (float) $product['height'],
            'volume' => round($volume, 4),
        ];
    }
}
