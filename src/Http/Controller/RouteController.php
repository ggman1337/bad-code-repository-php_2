<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\RouteService;
use App\Support\Request;
use App\Support\Response;

class RouteController
{
    public function __construct(private RouteService $routeService)
    {
    }

    public function calculate(Request $request, array $params = []): Response
    {
        $result = $this->routeService->calculate($request->body());
        return Response::json(['data' => $result]);
    }
}
