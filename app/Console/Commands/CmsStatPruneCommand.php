<?php

namespace App\Console\Commands;

use App\Jobs\PruneStatHitsJob;
use App\Services\Cms\Stats\StatService;
use App\Support\CmsRuntime;
use Illuminate\Console\Command;

class CmsStatPruneCommand extends Command
{
    protected $signature = 'cms:stat-prune
        {--days=90 : 保留天数}
        {--queue : 投递到队列}
        {--sync : 当前进程同步执行}';

    protected $description = '清理过期访问统计日志；可用 --queue / --sync 切换';

    public function handle(StatService $stats): int
    {
        $days = max(1, (int) $this->option('days'));
        $useQueue = $this->option('sync')
            ? false
            : ($this->option('queue') || CmsRuntime::mode('stat_prune') === CmsRuntime::MODE_QUEUE);

        if ($useQueue) {
            PruneStatHitsJob::dispatch($days);
            $this->info('已投递统计清理任务到队列「'.CmsRuntime::queueName('stat_prune').'」');

            return self::SUCCESS;
        }

        $deleted = $stats->prune($days);
        $this->info("已删除 {$deleted} 条早于 {$days} 天的访问记录");

        return self::SUCCESS;
    }
}
