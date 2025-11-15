<?php

declare(strict_types=1);

namespace Tests\Controller;

use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Новый товар',
            'weight' => 2.5,
            'length' => 15.0,
            'width' => 12.0,
            'height' => 8.0,
        ], $overrides);
    }

    public function testGetAllProductsShouldReturnList(): void
    {
        $this->createProductFixture();
        $response = $this->getJson('/products', $this->adminToken);
        $body = $this->assertSuccess($response);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['data']);
    }

    public function testGetAllProductsWithoutAuthShouldReturnForbidden(): void
    {
        $response = $this->getJson('/products');
        $this->assertError($response, 403);
    }

    public function testCreateProductAsAdminShouldSucceed(): void
    {
        $response = $this->postJson('/products', $this->productPayload(), $this->adminToken);
        $body = $this->assertSuccess($response, 201);
        $this->assertSame('Новый товар', $body['data']['name']);
    }

    public function testCreateProductAsManagerShouldReturnForbidden(): void
    {
        $response = $this->postJson('/products', $this->productPayload(), $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testCreateProductAsCourierShouldReturnForbidden(): void
    {
        $response = $this->postJson('/products', $this->productPayload(), $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testCreateProductWithInvalidDataShouldReturnBadRequest(): void
    {
        $payload = [
            'name' => '',
            'weight' => -1,
            'length' => 0,
            'width' => -5,
            'height' => 0,
        ];
        $response = $this->postJson('/products', $payload, $this->adminToken);
        $this->assertError($response, 400);
    }

    public function testUpdateProductAsAdminShouldSucceed(): void
    {
        $product = $this->createProductFixture();
        $payload = $this->productPayload([
            'name' => 'Обновленный товар',
            'weight' => 3.0,
        ]);
        $response = $this->putJson('/products/' . $product['id'], $payload, $this->adminToken);
        $body = $this->assertSuccess($response);
        $this->assertSame('Обновленный товар', $body['data']['name']);
        $this->assertSame(3.0, $body['data']['weight']);
    }

    public function testUpdateProductAsManagerShouldReturnForbidden(): void
    {
        $product = $this->createProductFixture();
        $response = $this->putJson('/products/' . $product['id'], $this->productPayload(), $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testUpdateNonExistentProductShouldReturnBadRequest(): void
    {
        $response = $this->putJson('/products/9999', $this->productPayload(), $this->adminToken);
        $this->assertError($response, 404);
    }

    public function testDeleteProductAsAdminShouldSucceed(): void
    {
        $product = $this->createProductFixture();
        $response = $this->deleteJson('/products/' . $product['id'], $this->adminToken);
        $this->assertSame(204, $response->status());
    }

    public function testDeleteProductAsManagerShouldReturnForbidden(): void
    {
        $product = $this->createProductFixture();
        $response = $this->deleteJson('/products/' . $product['id'], $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testDeleteProductAsCourierShouldReturnForbidden(): void
    {
        $product = $this->createProductFixture();
        $response = $this->deleteJson('/products/' . $product['id'], $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testDeleteNonExistentProductShouldReturnBadRequest(): void
    {
        $response = $this->deleteJson('/products/9999', $this->adminToken);
        $this->assertError($response, 404);
    }

    public function testGetProductsWithManagerTokenShouldSucceed(): void
    {
        $this->createProductFixture();
        $response = $this->getJson('/products', $this->managerToken);
        $this->assertSuccess($response);
    }

    public function testGetProductsWithCourierTokenShouldSucceed(): void
    {
        $this->createProductFixture();
        $response = $this->getJson('/products', $this->courierToken);
        $this->assertSuccess($response);
    }
}
