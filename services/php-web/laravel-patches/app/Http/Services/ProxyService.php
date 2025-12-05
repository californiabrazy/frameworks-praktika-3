<?php

namespace App\Http\Services;

use Illuminate\Http\Response;

class ProxyService
{
    private function base(): string {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function pipe(string $path): Response
    {
        $url = $this->base() . $path;
        try {
            $ctx = stream_context_create([
                'http' => ['timeout' => 5, 'ignore_errors' => true],
            ]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body === false || trim($body) === '') {
                $body = '{}';
            }
            json_decode($body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $body = '{}';
            }
            return new Response($body, 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return new Response('{"error":"upstream"}', 200, ['Content-Type' => 'application/json']);
        }
    }
}
