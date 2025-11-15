<?php

declare(strict_types=1);

namespace Tests\Controller;

use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    public function testLoginWithValidCredentialsReturnsTokenAndUserInfo(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => 'admin',
            'password' => 'admin123',
        ]);

        $body = $this->assertSuccess($response);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('user', $body);
        $this->assertSame((int) $this->adminUser['id'], $body['user']['id']);
        $this->assertSame('admin', $body['user']['login']);
    }

    public function testLoginWithInvalidLoginReturnsBadRequest(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => 'unknown',
            'password' => 'password',
        ]);

        $error = $this->assertError($response, 400);
        $this->assertSame('VALIDATION_FAILED', $error['code']);
    }

    public function testLoginWithInvalidPasswordReturnsBadRequest(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => 'admin',
            'password' => 'wrong',
        ]);

        $error = $this->assertError($response, 400);
        $this->assertSame('VALIDATION_FAILED', $error['code']);
    }

    public function testLoginWithEmptyLoginReturnsBadRequest(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => '',
            'password' => 'admin123',
        ]);

        $error = $this->assertError($response, 400);
        $this->assertArrayHasKey('details', $error);
        $this->assertArrayHasKey('login', $error['details']);
    }

    public function testLoginWithEmptyPasswordReturnsBadRequest(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => 'admin',
            'password' => '',
        ]);

        $error = $this->assertError($response, 400);
        $this->assertArrayHasKey('details', $error);
        $this->assertArrayHasKey('password', $error['details']);
    }

    public function testLoginWithManagerCredentialsReturnsManagerToken(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => 'manager',
            'password' => 'password',
        ]);

        $body = $this->assertSuccess($response);
        $this->assertSame('manager', $body['user']['role']);
    }

    public function testLoginWithCourierCredentialsReturnsCourierToken(): void
    {
        $response = $this->postJson('/auth/login', [
            'login' => 'courier',
            'password' => 'password',
        ]);

        $body = $this->assertSuccess($response);
        $this->assertSame('courier', $body['user']['role']);
    }
}
