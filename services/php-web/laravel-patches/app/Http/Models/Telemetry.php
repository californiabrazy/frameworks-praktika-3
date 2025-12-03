<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Telemetry extends Model
{
    protected $table = 'telemetry_legacy';

    protected $fillable = [
        'recorded_at',
        'voltage',
        'temp',
        'is_valid',
        'source_file',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'recorded_at' => 'datetime',
    ];
}
