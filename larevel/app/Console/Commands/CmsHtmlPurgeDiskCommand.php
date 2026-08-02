<?php

namespace App\Console\Commands;

use App\Models\Cms\Site;
use App\Services\Cms\DiskHtmlService;
use App\Support\CurrentSite;
use Illuminate\Console\Command;

class CmsHtmlPurgeDiskCommand extends Command
{
    protected $signature = 'cms:html-purge-disk {--site=}';

    protected $description = '清空当前站磁盘 HTML';

    public function handle(DiskHtmlService $disk): int
    {
        $siteId = $this->option('site') ? (int) $this->option('site') : Site::defaultId();
        if ($siteId) {
            CurrentSite::set($siteId);
        }
        $n = $disk->clear();
        $this->info("deleted={$n} path=".$disk->rootPath());

        return self::SUCCESS;
    }
}
