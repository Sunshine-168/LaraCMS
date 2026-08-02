<?php

namespace App\Console\Commands;

use App\Services\Cms\HtmlCacheService;
use Illuminate\Console\Command;

class CmsHtmlClearCommand extends Command
{
    protected $signature = 'cms:html-clear';

    protected $description = '清空全页 HTML 缓存';

    public function handle(HtmlCacheService $cache): int
    {
        $cache->forgetAll('artisan:html-clear');
        $this->info('全页 HTML 缓存已清空');

        return self::SUCCESS;
    }
}
