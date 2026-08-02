<?php

namespace App\Models\Cms;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Translatable;

    protected $table = 'cms_categories';

    protected $fillable = [
        'title', 'slug', 'type', 'model_id', 'parent_id', 'list_tpl', 'show_tpl',
        'category_tpl', 'is_menu', 'url', 'page_size', 'sort_order', 'status',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'slug' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'is_menu' => 'boolean',
        ];
    }

    public function modelDef(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'category_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->published();
    }

    /** 前台链接（外链栏目用库里的 url） */
    public function frontUrl(): string
    {
        $raw = $this->attributes['url'] ?? null;
        if ($this->type === 'link' && $raw) {
            return (string) $raw;
        }

        $prefix = \App\Support\UrlMode::localePrefix();
        $slug = $this->t('slug');
        $mode = \App\Support\UrlMode::current();

        return match ($mode) {
            \App\Support\UrlMode::CAT_ID => $prefix.'/c/'.$this->id,
            \App\Support\UrlMode::MODEL_CAT_ENTRY => $prefix.'/'.($this->modelDef?->slug ?: 'item').'/'.$slug,
            default => $prefix.'/'.$slug,
        };
    }
}
