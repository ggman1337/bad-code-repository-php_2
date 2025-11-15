<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class DeliveryStatus
{
    public const PLANNED = 'planned';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [self::PLANNED, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }
}
