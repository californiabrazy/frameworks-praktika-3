<?php

namespace App\Http\DTO;

class JwstImageDto
{
    public ?string $url;
    public ?string $description;

    public function __construct(array $data)
    {
        $this->url = $data['url'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'description' => $this->description,
        ];
    }
}
