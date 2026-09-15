<?php

namespace App\Console\Commands;

use App\Services\Cms\ThemeCatalog;
use Illuminate\Console\Command;

class CmsThemeMakeCommand extends Command
{
    protected $signature = 'cms:theme-make
        {slug : 新主题标识，如 brand}
        {--from=enterprise : 从哪套复制}
        {--name= : 显示名称}
        {--description= : 一句话介绍}';

    protected $description = '从现有主题复制一套独立外观';

    public function handle(ThemeCatalog $themes): int
    {
        $slug = (string) $this->argument('slug');
        $from = (string) $this->option('from');
        $name = trim((string) $this->option('name')) ?: $slug;
        $result = $themes->copy($from, $slug, $name, (string) $this->option('description'));
        if (! $result['ok']) {
            $this->error($result['msg']);

            return self::FAILURE;
        }
        $this->info($result['msg']);
        $this->line('目录 resources/views/themes/'.$slug);

        return self::SUCCESS;
    }
}
