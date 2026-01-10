<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

class Dimensions
{
    public function __construct(
        private float $length,
        private float $width,
        private float $height
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (float) ($data['length'] ?? 0),
            (float) ($data['width'] ?? 0),
            (float) ($data['height'] ?? 0)
        );
    }

    public function length(): float
    {
        return $this->length;
    }

    public function width(): float
    {
        return $this->width;
    }

    public function height(): float
    {
        return $this->height;
    }

    public function volume(): float
    {
        return ($this->length * $this->width * $this->height) / 1_000_000;
    }
}
