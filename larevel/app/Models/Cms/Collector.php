<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collector extends Model
{
    protected $table = 'cms_collectors';

    protected $fillable = [
        'name', 'type', 'source_url', 'model_id', 'category_id',
        'interval_minutes', 'limit_items', 'is_active', 'publish_immediately',
        'options', 'last_run_at', 'last_status', 'last_message',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
            'publish_immediately' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function modelDef(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CollectorLog::class, 'collector_id')->orderByDesc('id');
    }

    public function due(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if (! $this->last_run_at) {
            return true;
        }

        return $this->last_run_at->lte(now()->subMinutes(max(1, (int) $this->interval_minutes)));
    }
}
