<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(?string $role = null): array
    {
        if ($role) {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE role = :role ORDER BY id');
            $stmt->execute(['role' => $role]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM users ORDER BY id');
        }

        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user !== false ? $user : null;
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE login = :login');
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch();
        return $user !== false ? $user : null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (login, password_hash, name, role, created_at) VALUES (:login, :password_hash, :name, :role, :created_at)'
        );
        $stmt->execute([
            'login' => $data['login'],
            'password_hash' => $data['password_hash'],
            'name' => $data['name'],
            'role' => $data['role'],
            'created_at' => $data['created_at'],
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

        $sql = 'UPDATE users SET ' . implode(', ', $columns) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findManyByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($ids));
        $rows = $stmt->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = $row;
        }
        return $map;
    }
}
