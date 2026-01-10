<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

class Coordinates
{
    public function __construct(
        private float $latitude,
        private float $longitude
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['latitude'] ?? 0),
            (float) ($data['longitude'] ?? 0)
        );
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
