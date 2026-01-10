<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

class Capacity
{
    public function __construct(
        private float $maxWeight,
        private float $maxVolume
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['max_weight'] ?? 0),
            (float) ($data['max_volume'] ?? 0)
        );
    }

    public function maxWeight(): float
    {
        return $this->maxWeight;
    }

    public function maxVolume(): float
    {
        return $this->maxVolume;
    }
}
