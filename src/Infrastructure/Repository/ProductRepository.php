<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM products ORDER BY id');
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        return $product !== false ? $product : null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, weight, length, width, height) VALUES (:name, :weight, :length, :width, :height)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'weight' => $data['weight'],
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
        ]);

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

        $sql = 'UPDATE products SET ' . implode(', ', $columns) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findManyByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT * FROM products WHERE id IN (' . $placeholders . ')';
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
