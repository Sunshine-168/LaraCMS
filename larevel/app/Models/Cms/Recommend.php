<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recommend extends Model
{
    protected $table = 'cms_recommends';

    protected $fillable = ['name', 'title', 'type', 'query_params', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'query_params' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecommendItem::class, 'recommend_id')->orderBy('sort_order');
    }
}
