<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

class PasswordHasher
{
    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
