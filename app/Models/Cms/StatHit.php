<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StatHit extends Model
{
    use BelongsToSite;

    public $timestamps = false;

    protected $table = 'cms_stat_hits';

    protected $fillable = [
        'site_id','path', 'query', 'ip', 'visitor_hash', 'user_agent', 'referer',
        'locale', 'is_spider', 'spider_name', 'status_code', 'created_at',];

    protected function casts(): array
    {
        return [
            'is_spider' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function scopeHuman(Builder $query): Builder
    {
        return $query->where('is_spider', false);
    }

    public function scopeSpider(Builder $query): Builder
    {
        return $query->where('is_spider', true);
    }

    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
    }
}
