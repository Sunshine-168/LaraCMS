<?php

namespace App\Jobs;

use App\Services\Cms\Stats\StatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** 清理过期访问统计 */
class PruneStatHitsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $days = 90)
    {
        $queue = (string) config('cms.runtime.stat_prune_queue', 'cms');
        if ($queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(StatService $stats): void
    {
        $deleted = $stats->prune($this->days);
        Log::info("cms:stat-prune deleted={$deleted} days={$this->days}");
    }
}
