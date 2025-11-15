<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class UserRole
{
    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const COURIER = 'courier';

    public static function all(): array
    {
        return [self::ADMIN, self::MANAGER, self::COURIER];
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
