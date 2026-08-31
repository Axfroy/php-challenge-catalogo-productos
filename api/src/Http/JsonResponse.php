<?php

declare(strict_types=1);

namespace App\Http;

final readonly class JsonResponse
{
    /**
     * @param array<string,mixed>|null $body
     * @param array<string,string>     $headers
     */
    public function __construct(
        public int $status,
        public ?array $body = null,
        public array $headers = [],
    ) {
    }

    /** @param array<string,string> $headers */
    public static function error(int $status, string $message, array $headers = []): self
    {
        return new self($status, ['message' => $message], $headers);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        if ($this->body === null) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
