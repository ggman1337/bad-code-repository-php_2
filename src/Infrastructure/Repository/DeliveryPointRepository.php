<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class DeliveryPointRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByDelivery(int $deliveryId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM delivery_points WHERE delivery_id = :delivery_id ORDER BY sequence');
        $stmt->execute(['delivery_id' => $deliveryId]);
        return $stmt->fetchAll() ?: [];
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO delivery_points (delivery_id, sequence, latitude, longitude) VALUES (:delivery_id, :sequence, :latitude, :longitude)'
        );
        $stmt->execute($data);
        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM delivery_points WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $point = $stmt->fetch();
        return $point !== false ? $point : null;
    }

    public function findByDeliveryIds(array $deliveryIds): array
    {
        if (empty($deliveryIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($deliveryIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM delivery_points WHERE delivery_id IN ({$placeholders}) ORDER BY delivery_id, sequence"
        );
        $stmt->execute(array_values($deliveryIds));
        return $stmt->fetchAll() ?: [];
    }

    public function deleteByDelivery(int $deliveryId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM delivery_points WHERE delivery_id = :delivery_id');
        $stmt->execute(['delivery_id' => $deliveryId]);
    }
}
