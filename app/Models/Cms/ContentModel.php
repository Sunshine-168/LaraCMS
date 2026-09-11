<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentModel extends Model
{
    use BelongsToSite;

    protected $table = 'cms_models';

    protected $fillable = [
        'site_id', 'slug', 'name', 'kind', 'list_tpl', 'show_tpl',
        'sort_order', 'is_active', 'is_searchable',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_searchable' => 'boolean',
        ];
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
