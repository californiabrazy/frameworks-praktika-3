<?php

namespace App\Http\DTO;

class OsdrItemDto
{
    public ?int $id;
    public ?string $dataset_id;
    public ?string $title;
    public ?string $status;
    public ?string $updated_at;
    public ?string $inserted_at;
    public ?array $raw;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->dataset_id = $data['dataset_id'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        $this->inserted_at = $data['inserted_at'] ?? null;
        $this->raw = $data['raw'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dataset_id' => $this->dataset_id,
            'title' => $this->title,
            'status' => $this->status,
            'updated_at' => $this->updated_at,
            'inserted_at' => $this->inserted_at,
            'raw' => $this->raw,
        ];
    }
}
