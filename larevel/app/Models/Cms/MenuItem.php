<?php

namespace App\Models\Cms;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
