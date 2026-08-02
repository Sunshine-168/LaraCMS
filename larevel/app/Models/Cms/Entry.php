<?php

namespace App\Models\Cms;

use App\Support\Locale;
use App\Support\Translatable;
use App\Support\UrlMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entry extends Model
{
    use SoftDeletes;
    use Translatable;

    protected $table = 'cms_entries';

    protected $fillable = [
        'category_id', 'model_id', 'title', 'slug', 'excerpt', 'content',
        'meta_title', 'meta_description', 'attrs', 'cover', 'flags', 'search_text',
        'source_guid', 'collector_id',
        'status', 'sort_order', 'views', 'downloads_count', 'clicks',
        'is_homepage', 'published_at',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'slug' => 'array',
            'excerpt' => 'array',
            'content' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'attrs' => 'array',
            'is_homepage' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function modelDef(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'cms_entry_tag', 'entry_id', 'tag_id');
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

    public function attr(string $key, mixed $default = null): mixed
    {
        return data_get($this->attrs ?? [], $key, $default);
    }

    public function getUrlAttribute(): string
    {
        $prefix = UrlMode::localePrefix();

        $modelSlug = $this->modelDef?->slug;
        if (in_array($modelSlug, ['link'], true)) {
            return (string) ($this->attr('url') ?: '#');
        }

        $catSlug = $this->category?->t('slug') ?: 'item';
        $slug = $this->t('slug') ?: $this->id;
        $mode = UrlMode::current();

        if ($this->modelDef?->kind === 'single') {
            return match ($mode) {
                UrlMode::CAT_ID => $prefix.'/c/'.($this->category_id ?: 0),
                UrlMode::MODEL_CAT_ENTRY => $prefix.'/'.($modelSlug ?: 'page').'/'.$catSlug,
                default => $prefix.'/'.$catSlug,
            };
        }

        return match ($mode) {
            UrlMode::ENTRY_ID => $prefix.'/e/'.$this->id,
            UrlMode::CAT_ID => $prefix.'/c/'.($this->category_id ?: 0).'/'.$slug,
            UrlMode::MODEL_CAT_ENTRY => $prefix.'/'.($modelSlug ?: 'item').'/'.$catSlug.'/'.$slug,
            default => $prefix.'/'.$catSlug.'/'.$slug,
        };
    }

    public function rebuildSearchText(): void
    {
        $parts = [];
        foreach (['title', 'excerpt', 'content', 'slug'] as $field) {
            $val = $this->{$field};
            if (is_array($val)) {
                $parts = array_merge($parts, array_map('strval', array_values($val)));
            } elseif (is_string($val)) {
                $parts[] = $val;
            }
        }
        $this->search_text = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags(implode(' ', $parts))) ?: ''), 0, 5000);
    }

    public function hasFlag(string $flag): bool
    {
        $flags = array_filter(array_map('trim', explode(',', (string) $this->flags)));

        return in_array($flag, $flags, true);
    }
}
