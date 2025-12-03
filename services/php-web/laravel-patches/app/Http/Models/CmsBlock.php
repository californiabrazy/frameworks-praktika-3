<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CmsBlock extends Model
{
    protected $table = 'cms_blocks';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
