<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\ValueObject\UserRole;
use App\Infrastructure\Repository\DeliveryRepository;
use App\Infrastructure\Repository\UserRepository;
use App\Infrastructure\Security\PasswordHasher;
use App\Support\Exceptions\NotFoundException;
use App\Support\Exceptions\ValidationException;
use DateTimeImmutable;

class UserService
{
    public function __construct(
        private UserRepository $users,
        private DeliveryRepository $deliveries,
        private PasswordHasher $hasher
    ) {
    }

    public function list(?string $role = null): array
    {
        if ($role !== null && !UserRole::isValid($role)) {
            throw new ValidationException(['role' => 'Неизвестная роль']);
        }

        $users = $this->users->findAll($role);
        return array_map([$this, 'transform'], $users);
    }

    public function create(array $payload): array
    {
        $errors = [];
        $login = trim((string) ($payload['login'] ?? ''));
        $name = trim((string) ($payload['name'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $role = (string) ($payload['role'] ?? '');

        if ($login === '') {
            $errors['login'] = 'Логин обязателен';
        }
        if ($name === '') {
            $errors['name'] = 'Имя обязательно';
        }
        if ($password === '') {
            $errors['password'] = 'Пароль обязателен';
        }
        if ($role === '' || !UserRole::isValid($role)) {
            $errors['role'] = 'Некорректная роль';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        if ($this->users->findByLogin($login)) {
            throw new ValidationException(['login' => 'Логин уже используется']);
        }

        $user = $this->users->create([
            'login' => $login,
            'password_hash' => $this->hasher->hash($password),
            'name' => $name,
            'role' => $role,
            'created_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ]);

        return $this->transform($user);
    }

    public function update(int $id, array $payload): array
    {
        $user = $this->users->findById($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        $data = [];
        $errors = [];

        if (array_key_exists('login', $payload)) {
            $login = trim((string) $payload['login']);
            if ($login === '') {
                $errors['login'] = 'Логин не может быть пустым';
            } elseif ($login !== $user['login'] && $this->users->findByLogin($login)) {
                $errors['login'] = 'Логин уже используется';
            } else {
                $data['login'] = $login;
            }
        }

        if (array_key_exists('name', $payload)) {
            $name = trim((string) $payload['name']);
            if ($name === '') {
                $errors['name'] = 'Имя не может быть пустым';
            } else {
                $data['name'] = $name;
            }
        }

        if (array_key_exists('role', $payload)) {
            $role = (string) $payload['role'];
            if (!UserRole::isValid($role)) {
                $errors['role'] = 'Некорректная роль';
            } else {
                $data['role'] = $role;
            }
        }

        if (array_key_exists('password', $payload)) {
            $password = (string) $payload['password'];
            if ($password === '') {
                $errors['password'] = 'Пароль не может быть пустым';
            } else {
                $data['password_hash'] = $this->hasher->hash($password);
            }
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        if ($data === []) {
            return $this->transform($user);
        }

        $updated = $this->users->update($id, $data);
        return $this->transform($updated);
    }

    public function delete(int $id): void
    {
        $user = $this->users->findById($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        $deliveries = $this->deliveries->findByFilters(null, $id, null);
        $hasActive = array_filter($deliveries, function (array $delivery): bool {
            return in_array($delivery['status'], ['planned', 'in_progress'], true);
        });

        if ($hasActive) {
            throw new ValidationException([
                'id' => 'Пользователь задействован в активных доставках и не может быть удален',
            ]);
        }

        $this->users->delete($id);
    }

    private function transform(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'login' => $user['login'],
            'name' => $user['name'],
            'role' => $user['role'],
            'created_at' => $user['created_at'],
        ];
    }
}
