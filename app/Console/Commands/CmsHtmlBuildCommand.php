<?php

namespace App\Console\Commands;

use App\Models\Cms\Site;
use App\Services\Cms\DiskHtmlService;
use App\Support\CurrentSite;
use Illuminate\Console\Command;

class CmsHtmlBuildCommand extends Command
{
    protected $signature = 'cms:html-build {--entries=200} {--site=}';

    protected $description = '生成磁盘整站 HTML（按站点）';

    public function handle(DiskHtmlService $disk): int
    {
        if (! $disk->enabled()) {
            $this->error('CMS_DISK_HTML 未启用');

            return self::FAILURE;
        }
        $siteId = $this->option('site') ? (int) $this->option('site') : Site::defaultId();
        if (! $siteId) {
            $this->error('无可用站点');

            return self::FAILURE;
        }
        CurrentSite::set($siteId);
        $result = $disk->build((int) $this->option('entries'));
        $this->info("site={$siteId} ok={$result['ok']} fail={$result['fail']}");
        $this->line($disk->rootPath());

        return self::SUCCESS;
    }
}
