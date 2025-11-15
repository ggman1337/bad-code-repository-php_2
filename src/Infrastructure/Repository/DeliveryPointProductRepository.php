<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class DeliveryPointProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO delivery_point_products (delivery_point_id, product_id, quantity) VALUES (:delivery_point_id, :product_id, :quantity)'
        );
        $stmt->execute($data);
    }

    public function deleteByDeliveryPoint(int $deliveryPointId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM delivery_point_products WHERE delivery_point_id = :delivery_point_id');
        $stmt->execute(['delivery_point_id' => $deliveryPointId]);
    }

    public function findByDeliveryPointIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = <<<SQL
            SELECT dpp.*, p.name as product_name, p.weight, p.length, p.width, p.height
            FROM delivery_point_products dpp
            JOIN products p ON p.id = dpp.product_id
            WHERE dpp.delivery_point_id IN ({$placeholders})
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll() ?: [];
    }
}
