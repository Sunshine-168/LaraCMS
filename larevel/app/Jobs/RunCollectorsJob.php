<?php

namespace App\Jobs;

use App\Models\Cms\Collector;
use App\Services\Cms\Collect\CollectorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** 到期采集（可入队；多站点共用 queue:work） */
class RunCollectorsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly ?int $collectorId = null,
        public readonly bool $force = false,
    ) {
        $queue = (string) config('cms.runtime.collect_queue', 'cms');
        if ($queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(CollectorService $collectorService): void
    {
        if ($this->collectorId) {
            $collector = Collector::query()->withoutGlobalScopes()->find($this->collectorId);
            if (! $collector) {
                Log::warning('cms:collect job missing collector '.$this->collectorId);

                return;
            }
            if (! $this->force && ! $collector->due()) {
                return;
            }
            $result = $collectorService->run($collector);
            Log::info('cms:collect '.$result['msg'], $result['data'] ?? []);

            return;
        }

        if ($this->force) {
            $created = 0;
            Collector::query()->withoutGlobalScopes()->where('is_active', true)->each(function (Collector $c) use ($collectorService, &$created) {
                $r = $collectorService->run($c);
                $created += (int) ($r['data']['created'] ?? 0);
            });
            Log::info("cms:collect force created={$created}");

            return;
        }

        $result = $collectorService->runDue();
        Log::info('cms:collect '.$result['msg'], $result['data'] ?? []);
    }
}
