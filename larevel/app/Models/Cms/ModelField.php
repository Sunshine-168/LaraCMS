<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelField extends Model
{
    protected $table = 'model_field';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'normal');
    }
}
