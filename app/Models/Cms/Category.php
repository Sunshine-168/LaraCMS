<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use App\Support\Translatable;
use App\Support\UrlMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToSite;
    use Translatable;

    protected $table = 'cms_categories';

    protected $fillable = [
        'site_id', 'title', 'slug', 'type', 'model_id', 'parent_id', 'list_tpl', 'show_tpl',
        'category_tpl', 'is_menu', 'url', 'page_size', 'sort_order', 'status',
        'meta_title', 'meta_description', ];

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

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            'hub' => '频道',
            'list' => '列表',
            'single' => '单页',
            'link' => '外链',
            default => $type ?: '—',
        };
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'category_id');
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

        $prefix = UrlMode::localePrefix();
        $slug = $this->t('slug');
        $mode = UrlMode::current();

        return match ($mode) {
            UrlMode::CAT_ID => $prefix.'/c/'.$this->id,
            UrlMode::MODEL_CAT_ENTRY => $prefix.'/'.($this->modelDef?->slug ?: 'item').'/'.$slug,
            default => $prefix.'/'.$slug,
        };
    }
}
