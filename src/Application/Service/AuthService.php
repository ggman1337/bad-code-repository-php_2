<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Infrastructure\Repository\UserRepository;
use App\Infrastructure\Security\JwtManager;
use App\Infrastructure\Security\PasswordHasher;
use App\Support\Exceptions\ValidationException;
use DateTimeImmutable;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private JwtManager $jwtManager
    ) {
    }

    public function login(array $payload): array
    {
        $errors = [];
        $login = trim((string) ($payload['login'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($login === '') {
            $errors['login'] = 'Логин обязателен';
        }
        if ($password === '') {
            $errors['password'] = 'Пароль обязателен';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        $user = $this->users->findByLogin($login);
        if (!$user || !$this->hasher->verify($password, $user['password_hash'])) {
            throw new ValidationException(['credentials' => 'Invalid login or password']);
        }

        $token = $this->jwtManager->issueToken([
            'sub' => (int) $user['id'],
            'login' => $user['login'],
            'role' => $user['role'],
        ]);

        return [
            'token' => $token,
            'user' => $this->transformUser($user),
        ];
    }

    public function transformUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'login' => $user['login'],
            'name' => $user['name'],
            'role' => $user['role'],
            'created_at' => $user['created_at'],
        ];
    }

    public function resetAdminPassword(string $newPassword = 'admin123'): array
    {
        $admin = $this->users->findByLogin('admin');
        if (!$admin) {
            throw new ValidationException(['login' => 'Администратор не найден']);
        }

        $hash = $this->hasher->hash($newPassword);
        $updated = $this->users->update((int) $admin['id'], ['password_hash' => $hash]);

        return [
            'login' => $updated['login'],
            'role' => $updated['role'],
            'updated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ];
    }
}
