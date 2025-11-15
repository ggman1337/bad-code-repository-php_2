<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class VehicleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM vehicles ORDER BY id');
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $vehicle = $stmt->fetch();
        return $vehicle !== false ? $vehicle : null;
    }

    public function findByLicensePlate(string $license): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE license_plate = :license');
        $stmt->execute(['license' => $license]);
        $vehicle = $stmt->fetch();
        return $vehicle !== false ? $vehicle : null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO vehicles (brand, license_plate, max_weight, max_volume) VALUES (:brand, :license_plate, :max_weight, :max_volume)'
        );
        $stmt->execute([
            'brand' => $data['brand'],
            'license_plate' => $data['license_plate'],
            'max_weight' => $data['max_weight'],
            'max_volume' => $data['max_volume'],
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id);
    }

    public function update(int $id, array $data): array
    {
        $columns = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            $columns[] = sprintf('%s = :%s', $key, $key);
            $params[$key] = $value;
        }

        $sql = 'UPDATE vehicles SET ' . implode(', ', $columns) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findManyByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT * FROM vehicles WHERE id IN (' . $placeholders . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($ids));
        $rows = $stmt->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = $row;
        }
        return $map;
    }
}
