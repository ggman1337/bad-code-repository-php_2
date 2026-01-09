<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\AuthService;
use App\Support\Request;
use App\Support\Response;

class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(Request $request, array $params = []): Response
    {
        $data = $this->authService->login($request->body());
        return Response::json($data);
    }

    public function handleResetAdminPassword(Request $request, array $params = []): Response
    {
        $newPassword = $request->query('new_password', 'admin123');
        $result = $this->authService->resetAdminPassword((string) $newPassword);
        return Response::json(['data' => $result]);
    }
}
