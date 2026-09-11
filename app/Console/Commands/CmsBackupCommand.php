<?php

namespace App\Console\Commands;

use App\Services\Admin\SiteBackupService;
use Illuminate\Console\Command;

class CmsBackupCommand extends Command
{
    protected $signature = 'cms:backup {--restore= : 还原指定 zip 文件名（cms-backup-*.zip）}';

    protected $description = '备份或还原数据库 SQL 与 public 上传文件';

    public function handle(SiteBackupService $backups): int
    {
        $restore = (string) $this->option('restore');
        if ($restore !== '') {
            $result = $backups->restore($restore);
            if (! $result['ok']) {
                $this->error($result['msg']);

                return self::FAILURE;
            }
            $this->info($result['msg']);

            return self::SUCCESS;
        }

        $result = $backups->create();
        if (! $result['ok']) {
            $this->error($result['msg']);

            return self::FAILURE;
        }

        $this->info($result['msg'].'：'.$result['data']['path']);

        return self::SUCCESS;
    }
}
