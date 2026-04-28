<?php

declare(strict_types=1);

class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $payload = [];

        foreach ($keys as $key) {
            $payload[$key] = $this->input($key);
        }

        return $payload;
    }
}
