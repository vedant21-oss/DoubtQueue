<?php

declare(strict_types=1);

namespace DoubtQueue\Http;

use JsonException;

class Response
{
    public function __construct(public readonly int $status, public readonly array $body)
    {
    }

    public static function error(int $status, string $message): self
    {
        return new self($status, ['status' => $status, 'message' => $message]);
    }

    public function send(): void
    {
        $response = $this;

        try {
            $body = $response->encode();
        } catch (JsonException $e) {
            error_log((string) $e);

            $response = self::error(500, 'Something went wrong');
            $body = $response->encode();
        }

        http_response_code($response->status);
        header('Content-Type: application/json');
        echo $body;
    }

    /**
     * @throws JsonException when the body cannot be encoded, for example
     *                       because a string in it is not valid UTF-8.
     */
    private function encode(): string
    {
        return json_encode($this->body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
