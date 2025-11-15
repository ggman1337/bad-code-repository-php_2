<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\DeliveryService;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;

class DeliveryController
{
    public function __construct(private DeliveryService $deliveries)
    {
    }

    public function index(Request $request, array $params = []): Response
    {
        $filters = [
            'date' => $request->query('date'),
            'courier_id' => $request->query('courier_id'),
            'status' => $request->query('status'),
        ];
        $data = $this->deliveries->list($filters);
        return Response::json(['data' => $data]);
    }

    public function show(Request $request, array $params = []): Response
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new ValidationException(['id' => 'Некорректный ID доставки']);
        }
        $delivery = $this->deliveries->get($id);
        return Response::json(['data' => $delivery]);
    }

    public function store(Request $request, array $params = []): Response
    {
        $user = $request->user() ?? [];
        $delivery = $this->deliveries->create($request->body(), $user);
        return Response::json(['data' => $delivery], 201);
    }

    public function update(Request $request, array $params = []): Response
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new ValidationException(['id' => 'Некорректный ID доставки']);
        }
        $delivery = $this->deliveries->update($id, $request->body());
        return Response::json(['data' => $delivery]);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new ValidationException(['id' => 'Некорректный ID доставки']);
        }
        $this->deliveries->delete($id);
        return Response::json([], 204);
    }

    public function generate(Request $request, array $params = []): Response
    {
        $user = $request->user() ?? [];
        $result = $this->deliveries->generate($request->body(), $user);
        return Response::json(['data' => $result]);
    }
}
