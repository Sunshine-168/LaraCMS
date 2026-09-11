<?php

namespace App\Console\Commands;

use App\Services\Admin\SiteBackupService;
use Illuminate\Console\Command;

class CmsBackupCommand extends Command
{
    protected $signature = 'cms:backup';

    protected $description = '备份数据库 SQL 与 public 上传文件到 storage/app/backups';

    public function handle(SiteBackupService $backups): int
    {
        $result = $backups->create();
        if (! $result['ok']) {
            $this->error($result['msg']);

            return self::FAILURE;
        }

        $this->info($result['msg'].'：'.$result['data']['path']);

        return self::SUCCESS;
    }
}
