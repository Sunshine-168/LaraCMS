<?php

namespace App\Console\Commands;

use App\Services\Cms\HtmlCacheService;
use Illuminate\Console\Command;

class CmsHtmlWarmCommand extends Command
{
    protected $signature = 'cms:html-warm {--entries=30 : 预热最近发布条数}';

    protected $description = '预热全页 HTML 缓存（首页/栏目/最近内容）';

    public function handle(HtmlCacheService $cache): int
    {
        if (! $cache->enabled()) {
            $this->warn('全页缓存未启用，请先在后台设置开启');

            return self::FAILURE;
        }

        $result = $cache->warm(max(1, (int) $this->option('entries')));
        $this->info("预热完成：成功 {$result['ok']}，失败 {$result['fail']}，共 ".count($result['urls']).' URL');

        return $result['fail'] > 0 && $result['ok'] === 0 ? self::FAILURE : self::SUCCESS;
    }
}
