<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectorLog extends Model
{
    protected $table = 'cms_collector_logs';

    protected $fillable = [
        'collector_id', 'status', 'fetched', 'created', 'skipped', 'message',
    ];

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class, 'collector_id');
    }
}
