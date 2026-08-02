<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentModel extends Model
{
    protected $table = 'cms_models';

    protected $fillable = [
        'slug', 'name', 'kind', 'list_tpl', 'show_tpl', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ModelField::class, 'model_id')->orderBy('sort_order');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'model_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'model_id');
    }
}
