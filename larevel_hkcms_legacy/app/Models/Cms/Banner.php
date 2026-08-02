<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $table = 'banner';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'normal');
    }

    public function recommend(): BelongsTo
    {
        return $this->belongsTo(Recommend::class, 'recommend_id');
    }
}
