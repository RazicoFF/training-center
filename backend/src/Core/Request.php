<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var array<string,string> */
    private array $params = [];

    /** @var array<string,mixed>|null */
    private ?array $userClaims = null;

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed> $body
     * @param array<string,mixed> $formBody
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers = [],
        private readonly array $body = [],
        private readonly array $formBody = []
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }

        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        $body = is_array($body) ? $body : [];

        $formBody = $_POST;

        return new self($method, $path, $headers, $body, $formBody);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function jsonBody(): array
    {
        return $this->body;
    }

    public function formBody(): array
    {
        return $this->formBody;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtoupper($name)] ?? null;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }

    public function setUser(array $claims): void
    {
        $this->userClaims = $claims;
    }

    public function user(): ?array
    {
        return $this->userClaims;
    }
}
