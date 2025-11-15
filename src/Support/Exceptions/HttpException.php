<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use Exception;

class HttpException extends Exception
{
    public function __construct(
        protected int $statusCode,
        string $message,
        protected array $payload = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
