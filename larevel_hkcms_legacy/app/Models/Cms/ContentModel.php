<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentModel extends Model
{
    protected $table = 'model';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'normal');
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class, 'model_id');
    }
}
