<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class DeliveryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM deliveries WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $delivery = $stmt->fetch();
        return $delivery !== false ? $delivery : null;
    }

    public function findManyByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM deliveries WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll() ?: [];
    }

    public function findByFilters(?string $date, ?int $courierId, ?string $status): array
    {
        $clauses = [];
        $params = [];
        if ($date) {
            $clauses[] = 'delivery_date = :date';
            $params['date'] = $date;
        }
        if ($courierId) {
            $clauses[] = 'courier_id = :courier_id';
            $params['courier_id'] = $courierId;
        }
        if ($status) {
            $clauses[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT * FROM deliveries';
        if ($clauses) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $sql .= ' ORDER BY delivery_date, time_start';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findCourierDeliveries(int $courierId, array $filters): array
    {
        $clauses = ['courier_id = :courier_id'];
        $params = ['courier_id' => $courierId];

        if (!empty($filters['date'])) {
            $clauses[] = 'delivery_date = :date';
            $params['date'] = $filters['date'];
        }
        if (!empty($filters['status'])) {
            $clauses[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $clauses[] = 'delivery_date BETWEEN :date_from AND :date_to';
            $params['date_from'] = $filters['date_from'];
            $params['date_to'] = $filters['date_to'];
        }

        $sql = 'SELECT * FROM deliveries WHERE ' . implode(' AND ', $clauses) . ' ORDER BY delivery_date, time_start';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO deliveries (courier_id, vehicle_id, created_by, delivery_date, time_start, time_end, status, created_at, updated_at)
            VALUES (:courier_id, :vehicle_id, :created_by, :delivery_date, :time_start, :time_end, :status, :created_at, :updated_at)'
        );
        $stmt->execute($data);
        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): array
    {
        $columns = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            $columns[] = sprintf('%s = :%s', $key, $key);
            $params[$key] = $value;
        }

        $sql = 'UPDATE deliveries SET ' . implode(', ', $columns) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM deliveries WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findByVehicleOverlapping(string $date, int $vehicleId, string $timeStart, string $timeEnd): array
    {
        $sql = <<<'SQL'
            SELECT * FROM deliveries
            WHERE delivery_date = :date
              AND vehicle_id = :vehicle_id
              AND status NOT IN ('cancelled', 'completed')
              AND (
                    (time_start <= :time_start AND time_end > :time_start) OR
                    (time_start < :time_end AND time_end >= :time_end) OR
                    (time_start >= :time_start AND time_end <= :time_end)
                  )
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'date' => $date,
            'vehicle_id' => $vehicleId,
            'time_start' => $timeStart,
            'time_end' => $timeEnd,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByProductId(int $productId): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT d.*
            FROM deliveries d
            JOIN delivery_points dp ON dp.delivery_id = d.id
            JOIN delivery_point_products dpp ON dpp.delivery_point_id = dp.id
            WHERE dpp.product_id = :product_id
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByVehicle(int $vehicleId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM deliveries WHERE vehicle_id = :vehicle_id');
        $stmt->execute(['vehicle_id' => $vehicleId]);
        return $stmt->fetchAll() ?: [];
    }
}
