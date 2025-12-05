<?php

namespace App\Http\DTO;

class IssLastDto
{
    public ?array $payload;
    public ?string $fetched_at;

    public function __construct(array $data)
    {
        $this->payload = $data['payload'] ?? null;
        $this->fetched_at = $data['fetched_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'payload' => $this->payload,
            'fetched_at' => $this->fetched_at,
        ];
    }
}
