<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\ProductService;
use App\Support\Exceptions\ValidationException;
use App\Support\Request;
use App\Support\Response;

class ProductController
{
    public function __construct(private ProductService $products)
    {
    }

    public function index(Request $request, array $params = []): Response
    {
        $data = $this->products->list();
        return Response::json(['data' => $data]);
    }

    public function store(Request $request, array $params = []): Response
    {
        $product = $this->products->create($request->body());
        return Response::json(['data' => $product], 201);
    }

    public function update(Request $request, array $params = []): Response
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new ValidationException(['id' => 'Некорректный ID товара']);
        }
        $product = $this->products->update($id, $request->body());
        return Response::json(['data' => $product]);
    }

    public function destroy(Request $request, array $params = []): Response
    {
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        if ($id <= 0) {
            throw new ValidationException(['id' => 'Некорректный ID товара']);
        }
        $this->products->delete($id);
        return Response::json([], 204);
    }
}
