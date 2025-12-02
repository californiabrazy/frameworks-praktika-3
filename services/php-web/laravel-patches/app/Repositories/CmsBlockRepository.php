<?php

namespace App\Repositories;

use App\CmsBlock;
use Illuminate\Support\Facades\Cache;

class CmsBlockRepository
{
    protected $model;

    public function __construct(CmsBlock $model)
    {
        $this->model = $model;
    }

    public function getActiveBlocksBySlug(string $slug): array
    {
        $cacheKey = "cms_blocks_{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug) {
            return $this->model->where('slug', $slug)
                ->where('is_active', true)
                ->orderBy('id', 'asc')
                ->get(['title', 'content'])
                ->toArray();
        });
    }
}
