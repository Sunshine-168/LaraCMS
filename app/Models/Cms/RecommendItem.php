<?php

namespace App\Models\Cms;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendItem extends Model
{
    use Translatable;

    protected $table = 'cms_recommend_items';

    protected $fillable = [
        'recommend_id', 'title', 'notes', 'image', 'url', 'html',
        'entry_id', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'notes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function recommend(): BelongsTo
    {
        return $this->belongsTo(Recommend::class, 'recommend_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
