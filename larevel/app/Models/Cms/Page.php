<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToSite;

    use Translatable;

    protected $table = 'cms_pages';

    protected $fillable = [
        'site_id','title', 'slug', 'content', 'meta_title', 'meta_description',
        'parent_id', 'is_homepage', 'status', 'sort_order', 'published_at',];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'slug' => 'array',
            'content' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'is_homepage' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeHomepage(Builder $query): Builder
    {
        return $query->where('is_homepage', true);
    }
}
