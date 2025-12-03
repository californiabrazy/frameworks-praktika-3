<?php

namespace App\Http\Repositories;

use App\Http\Models\CmsPage;

class CmsPageRepository
{
    public function getPageBySlug(string $slug)
    {
        return CmsPage::where('slug', $slug)->first();
    }
}
