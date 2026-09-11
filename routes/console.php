<?php

use App\Jobs\PruneStatHitsJob;
use App\Jobs\RunCollectorsJob;
use App\Support\CmsRuntime;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (! CmsRuntime::scheduleEnabled()) {
    return;
}

// 采集：schedule=同步命令；queue=只投递 Job；off=不注册
match (CmsRuntime::collectMode()) {
    CmsRuntime::MODE_QUEUE => Schedule::job(new RunCollectorsJob)
        ->everyMinute()
        ->name('cms-collectors')
        ->withoutOverlapping(10),
    CmsRuntime::MODE_SCHEDULE => Schedule::command('cms:collect')
        ->everyMinute()
        ->name('cms-collectors')
        ->withoutOverlapping(10),
    default => null,
};

// 统计清理
match (CmsRuntime::mode('stat_prune')) {
    CmsRuntime::MODE_QUEUE => Schedule::job(new PruneStatHitsJob(90))
        ->dailyAt('03:20')
        ->name('cms-stat-prune')
        ->withoutOverlapping(),
    CmsRuntime::MODE_SCHEDULE => Schedule::command('cms:stat-prune --days=90')
        ->dailyAt('03:20')
        ->name('cms-stat-prune')
        ->withoutOverlapping(),
    default => null,
};
