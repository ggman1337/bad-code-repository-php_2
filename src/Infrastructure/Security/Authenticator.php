<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Repository\UserRepository;
use App\Support\Exceptions\UnauthorizedException;
use App\Support\Request;

class Authenticator
{
    public function __construct(
        private JwtManager $jwtManager,
        private UserRepository $users
    ) {
    }

    public function attachUser(Request $request): void
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return;
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return;
        }

        try {
            $payload = $this->jwtManager->decode($token);
        } catch (UnauthorizedException $exception) {
            throw $exception;
        }

        $userId = (int) ($payload['sub'] ?? 0);
        if ($userId <= 0) {
            throw new UnauthorizedException('Token has no subject');
        }

        $user = $this->users->findById($userId);
        if (!$user) {
            throw new UnauthorizedException('User not found for token');
        }

        $request->setUser($user);
    }
}
