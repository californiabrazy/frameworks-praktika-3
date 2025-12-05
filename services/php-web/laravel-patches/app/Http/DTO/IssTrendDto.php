<?php

namespace App\Http\DTO;

class IssTrendDto
{
    public ?bool $movement;
    public ?float $delta_km;
    public ?float $dt_sec;
    public ?float $velocity_kmh;

    public function __construct(array $data)
    {
        $this->movement = $data['movement'] ?? null;
        $this->delta_km = $data['delta_km'] ?? null;
        $this->dt_sec = $data['dt_sec'] ?? null;
        $this->velocity_kmh = $data['velocity_kmh'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'movement' => $this->movement,
            'delta_km' => $this->delta_km,
            'dt_sec' => $this->dt_sec,
            'velocity_kmh' => $this->velocity_kmh,
        ];
    }
}
