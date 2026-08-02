<?php

namespace App\Console\Commands;

use App\Jobs\RunCollectorsJob;
use App\Models\Cms\Collector;
use App\Services\Cms\Collect\CollectorService;
use App\Support\CmsRuntime;
use Illuminate\Console\Command;

class CmsCollectCommand extends Command
{
    protected $signature = 'cms:collect
        {--id= : 指定采集器 ID}
        {--force : 忽略间隔强制运行}
        {--queue : 投递到队列（覆盖 CMS_COLLECT_MODE）}
        {--sync : 当前进程同步执行（覆盖 CMS_COLLECT_MODE）}';

    protected $description = '运行 CMS 定时采集（RSS/JSON）；可用 --queue / --sync 切换';

    public function handle(CollectorService $service): int
    {
        $useQueue = $this->resolveUseQueue();

        if ($useQueue) {
            $id = $this->option('id') ? (int) $this->option('id') : null;
            RunCollectorsJob::dispatch($id, (bool) $this->option('force'));
            $this->info('已投递采集任务到队列「'.CmsRuntime::queueName('collect').'」');

            return self::SUCCESS;
        }

        return $this->runSync($service);
    }

    protected function resolveUseQueue(): bool
    {
        if ($this->option('sync')) {
            return false;
        }
        if ($this->option('queue')) {
            return true;
        }

        return CmsRuntime::collectMode() === CmsRuntime::MODE_QUEUE;
    }

    protected function runSync(CollectorService $service): int
    {
        $id = $this->option('id');
        if ($id) {
            $collector = Collector::query()->find($id);
            if (! $collector) {
                $this->error('采集器不存在');

                return self::FAILURE;
            }
            if (! $this->option('force') && ! $collector->due()) {
                $this->warn('未到采集间隔，使用 --force 强制执行');

                return self::SUCCESS;
            }
            $result = $service->run($collector);
            $this->{$result['ok'] ? 'info' : 'error'}($result['msg']);

            return $result['ok'] ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('force')) {
            $created = 0;
            Collector::query()->where('is_active', true)->each(function (Collector $c) use ($service, &$created) {
                $r = $service->run($c);
                $this->line(($r['ok'] ? '[OK] ' : '[FAIL] ').$c->name.': '.$r['msg']);
                $created += (int) ($r['data']['created'] ?? 0);
            });
            $this->info("强制采集完成，新建 {$created} 条");

            return self::SUCCESS;
        }

        $result = $service->runDue();
        $this->info($result['msg']);

        return self::SUCCESS;
    }
}
