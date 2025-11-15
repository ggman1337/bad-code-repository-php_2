<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Support\Config;
use App\Support\Exceptions\UnauthorizedException;

class JwtManager
{
    public function __construct(private Config $config)
    {
    }

    public function issueToken(array $claims): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => 'courier-management-system',
            'iat' => $now,
            'exp' => $now + (int) $this->config->get('app.jwt_ttl', 28800),
        ], $claims);

        return $this->encode($payload);
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new UnauthorizedException('Malformed token');
        }

        [$header64, $payload64, $signature] = $parts;
        $expected = $this->sign("{$header64}.{$payload64}");
        if (!hash_equals($expected, $signature)) {
            throw new UnauthorizedException('Invalid token signature');
        }

        $payload = json_decode($this->base64UrlDecode($payload64), true);
        if (!is_array($payload)) {
            throw new UnauthorizedException('Invalid token payload');
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new UnauthorizedException('Token expired');
        }

        return $payload;
    }

    private function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE)),
        ];
        $signature = $this->sign(implode('.', $segments));
        $segments[] = $signature;
        return implode('.', $segments);
    }

    private function sign(string $data): string
    {
        $secret = (string) $this->config->get('app.jwt_secret', 'change-me');
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $b64 = strtr($data, '-_', '+/');
        return base64_decode($b64 . str_repeat('=', (4 - strlen($b64) % 4) % 4));
    }
}
