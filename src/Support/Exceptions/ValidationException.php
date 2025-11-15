<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

class ValidationException extends HttpException
{
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct(400, $message, ['errors' => $errors]);
    }
}
