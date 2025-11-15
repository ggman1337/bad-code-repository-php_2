<?php

declare(strict_types=1);

namespace Tests\Controller;

use Tests\TestCase;

class UserControllerTest extends TestCase
{
    private function userRequest(array $overrides = []): array
    {
        return array_merge([
            'login' => 'user' . random_int(1000, 9999),
            'password' => 'password123',
            'name' => 'Новый Пользователь',
            'role' => 'courier',
        ], $overrides);
    }

    public function testGetAllUsersAsAdminShouldSucceed(): void
    {
        $response = $this->getJson('/users', $this->adminToken);
        $body = $this->assertSuccess($response);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(3, $body['data']);
    }

    public function testGetAllUsersAsManagerShouldReturnForbidden(): void
    {
        $response = $this->getJson('/users', $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testGetAllUsersAsCourierShouldReturnForbidden(): void
    {
        $response = $this->getJson('/users', $this->courierToken);
        $this->assertError($response, 403);
    }

    public function testGetAllUsersWithoutAuthShouldReturnForbidden(): void
    {
        $response = $this->getJson('/users');
        $this->assertError($response, 403);
    }

    public function testGetUsersFilteredByRole(): void
    {
        $response = $this->getJson('/users', $this->adminToken, ['role' => 'courier']);
        $body = $this->assertSuccess($response);
        $this->assertCount(1, $body['data']);
        $this->assertSame('courier', $body['data'][0]['role']);
    }

    public function testCreateUserAsAdminShouldSucceed(): void
    {
        $payload = $this->userRequest(['login' => 'newcourier']);
        $response = $this->postJson('/users', $payload, $this->adminToken);
        $body = $this->assertSuccess($response, 201);
        $this->assertSame('newcourier', $body['data']['login']);
        $this->assertSame('courier', $body['data']['role']);
    }

    public function testCreateUserAsManagerShouldReturnForbidden(): void
    {
        $payload = $this->userRequest();
        $response = $this->postJson('/users', $payload, $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testCreateUserWithDuplicateLoginShouldReturnBadRequest(): void
    {
        $payload = $this->userRequest(['login' => 'admin']);
        $response = $this->postJson('/users', $payload, $this->adminToken);
        $this->assertError($response, 400);
    }

    public function testCreateUserWithInvalidDataShouldReturnBadRequest(): void
    {
        $payload = [
            'login' => '',
            'password' => '',
            'name' => '',
            'role' => 'courier',
        ];
        $response = $this->postJson('/users', $payload, $this->adminToken);
        $this->assertError($response, 400);
    }

    public function testCreateManagerUserShouldSucceed(): void
    {
        $payload = $this->userRequest([
            'login' => 'newmanager',
            'role' => 'manager',
        ]);
        $response = $this->postJson('/users', $payload, $this->adminToken);
        $body = $this->assertSuccess($response, 201);
        $this->assertSame('manager', $body['data']['role']);
    }

    public function testUpdateUserAsAdminShouldSucceed(): void
    {
        $payload = $this->userRequest();
        $created = $this->postJson('/users', $payload, $this->adminToken);
        $userId = $this->assertSuccess($created, 201)['data']['id'];

        $update = $this->putJson('/users/' . $userId, [
            'name' => 'Обновленное Имя',
            'login' => 'updatedcourier',
            'role' => 'manager',
            'password' => 'newpassword',
        ], $this->adminToken);
        $body = $this->assertSuccess($update);
        $this->assertSame('Обновленное Имя', $body['data']['name']);
        $this->assertSame('updatedcourier', $body['data']['login']);
        $this->assertSame('manager', $body['data']['role']);
    }

    public function testUpdateUserAsManagerShouldReturnForbidden(): void
    {
        $response = $this->putJson('/users/' . $this->courierUser['id'], [
            'name' => 'New Name',
        ], $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testUpdateUserWithDuplicateLoginShouldReturnBadRequest(): void
    {
        $payload = $this->putJson('/users/' . $this->courierUser['id'], [
            'login' => 'admin',
        ], $this->adminToken);
        $this->assertError($payload, 400);
    }

    public function testUpdateNonExistentUserShouldReturnBadRequest(): void
    {
        $response = $this->putJson('/users/9999', [
            'name' => 'Ghost',
        ], $this->adminToken);
        $this->assertError($response, 404);
    }

    public function testUpdateUserPartialDataShouldSucceed(): void
    {
        $response = $this->putJson('/users/' . $this->courierUser['id'], [
            'name' => 'Только Новое Имя',
        ], $this->adminToken);
        $body = $this->assertSuccess($response);
        $this->assertSame('Только Новое Имя', $body['data']['name']);
        $this->assertSame($this->courierUser['login'], $body['data']['login']);
    }

    public function testDeleteUserAsAdminShouldSucceed(): void
    {
        $payload = $this->userRequest(['login' => 'todelete']);
        $created = $this->postJson('/users', $payload, $this->adminToken);
        $userId = $this->assertSuccess($created, 201)['data']['id'];

        $response = $this->deleteJson('/users/' . $userId, $this->adminToken);
        $this->assertSame(204, $response->status());
    }

    public function testDeleteUserAsManagerShouldReturnForbidden(): void
    {
        $response = $this->deleteJson('/users/' . $this->courierUser['id'], $this->managerToken);
        $this->assertError($response, 403);
    }

    public function testDeleteNonExistentUserShouldReturnBadRequest(): void
    {
        $response = $this->deleteJson('/users/9999', $this->adminToken);
        $this->assertError($response, 404);
    }
}
