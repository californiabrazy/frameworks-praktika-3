<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $table = 'cms_pages';

    protected $fillable = [
        'slug',
        'title',
        'body',
    ];
}
