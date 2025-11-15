<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct(401, $message);
    }
}
