<?php

namespace App\Models\Cms;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use Translatable;

    protected $table = 'cms_menu_items';

    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'type', 'url', 'reference_id', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
