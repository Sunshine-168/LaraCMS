<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'category';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'normal');
    }

    public function modelDef(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->active()->orderBy('weigh');
    }

    public function getUrlAttribute(mixed $value = null): string
    {
        if (($this->type ?? '') === 'link' && $value !== null && $value !== '') {
            if (is_numeric($value)) {
                $target = static::query()->active()->find((int) $value);

                return $target
                    ? route('cms.category.show', ['catname' => $target->name])
                    : route('cms.category.show', ['catname' => $this->name]);
            }

            return (string) $value;
        }

        return route('cms.category.show', ['catname' => $this->name]);
    }

    public function url(): string
    {
        return $this->url;
    }
}
