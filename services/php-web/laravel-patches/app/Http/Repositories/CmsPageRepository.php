<?php

namespace App\Http\Repositories;

use App\Http\Models\CmsPage;
use App\Http\Support\XssHelper;

class CmsPageRepository
{
    public function getPageBySlug(string $slug)
    {
        $page = CmsPage::where('slug', $slug)->first();
        if ($page) {
            $page->body = XssHelper::sanitize($page->body);
        }
        return $page;
    }
}
