<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recommend extends Model
{
    protected $table = 'recommend';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'normal');
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class, 'recommend_id');
    }
}
