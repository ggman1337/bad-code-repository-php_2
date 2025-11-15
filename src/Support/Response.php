<?php

declare(strict_types=1);

namespace App\Support;

class Response
{
    public function __construct(
        private array $body,
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new self($data, $status, $headers);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }
        echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): array
    {
        return $this->body;
    }
}
