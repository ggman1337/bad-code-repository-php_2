<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

class DeliveryWindow
{
    public function __construct(
        private string $date,
        private string $timeStart,
        private string $timeEnd
    ) {
    }

    public function date(): string
    {
        return $this->date;
    }

    public function timeStart(): string
    {
        return $this->timeStart;
    }

    public function timeEnd(): string
    {
        return $this->timeEnd;
    }
}
