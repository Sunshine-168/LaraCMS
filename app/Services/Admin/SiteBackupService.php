<?php

namespace App\Services\Admin;

use App\Models\Cms\Setting;
use App\Services\Cms\HtmlCacheService;
use App\Support\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SiteBackupService
{
    private const KEEP = 5;

    /** @var list<string> */
    private const SKIP_TABLES = [
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'sqlite_sequence',
    ];

    public function directory(): string
    {
        $dir = storage_path(app()->runningUnitTests() ? 'framework/testing/backups' : 'app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /** @return list<array{file: string, size: int, mtime: int}> */
    public function list(): array
    {
        $dir = $this->directory();
        $files = glob($dir.DIRECTORY_SEPARATOR.'cms-backup-*.zip') ?: [];
        rsort($files);
        $out = [];
        foreach ($files as $path) {
            $out[] = [
                'file' => basename($path),
                'size' => (int) filesize($path),
                'mtime' => (int) filemtime($path),
            ];
        }

        return $out;
    }

    public function pathFor(string $file): ?string
    {
        if (! preg_match('/^cms-backup-\d{8}-\d{6}\.zip$/', $file)) {
            return null;
        }
        $path = $this->directory().DIRECTORY_SEPARATOR.$file;

        return is_file($path) ? $path : null;
    }

    /** 打包库 SQL + public 上传文件（不含 .env） */
    public function create(): array
    {
        if (! class_exists(ZipArchive::class)) {
            return Result::fail('这台服务器还不能打包备份，请联系运维');
        }

        $stamp = now()->format('Ymd-His');
        $filename = 'cms-backup-'.$stamp.'.zip';
        $zipPath = $this->directory().DIRECTORY_SEPARATOR.$filename;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return Result::fail('无法创建备份文件');
        }

        $zip->addFromString('manifest.json', json_encode([
            'created_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
            'db_connection' => config('database.default'),
            'php' => PHP_VERSION,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $zip->addFromString('database.sql', $this->dumpSql());

        $sqlite = config('database.connections.sqlite.database');
        if (config('database.default') === 'sqlite' && is_string($sqlite) && is_file($sqlite)) {
            $zip->addFile($sqlite, 'database.sqlite');
        }

        foreach (Storage::disk('public')->allFiles() as $rel) {
            $full = Storage::disk('public')->path($rel);
            if (is_file($full)) {
                $zip->addFile($full, 'uploads/'.$rel);
            }
        }

        $zip->close();
        $this->prune();

        return Result::ok([
            'file' => $filename,
            'path' => $zipPath,
            'size' => (int) filesize($zipPath),
        ], '备份已生成');
    }

    /** 还原备份：覆盖数据表并写回 public 上传文件 */
    public function restore(string $file): array
    {
        $path = $this->pathFor($file);
        if (! $path) {
            return Result::fail('备份不存在');
        }
        if (! class_exists(ZipArchive::class)) {
            return Result::fail('这台服务器还不能还原备份，请联系运维');
        }

        $tmp = storage_path('app/backup-restore-'.uniqid());
        File::makeDirectory($tmp, 0755, true);
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            File::deleteDirectory($tmp);

            return Result::fail('无法打开备份');
        }
        $zip->extractTo($tmp);
        $zip->close();

        try {
            $sqlFile = $tmp.DIRECTORY_SEPARATOR.'database.sql';
            if (! is_file($sqlFile)) {
                return Result::fail('备份内没有 database.sql');
            }
            $this->importSql((string) file_get_contents($sqlFile));
            $this->restoreUploads($tmp.DIRECTORY_SEPARATOR.'uploads');
            Setting::forgetCacheForSite();
            app(HtmlCacheService::class)->forgetAll('backup-restore');
        } catch (\Throwable $e) {
            return Result::fail('还原失败：'.$e->getMessage());
        } finally {
            File::deleteDirectory($tmp);
        }

        return Result::ok(null, '备份已还原，请刷新页面');
    }

    protected function restoreUploads(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = File::allFiles($dir);
        foreach ($files as $file) {
            $rel = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($dir))), '/');
            if ($rel === '') {
                continue;
            }
            Storage::disk('public')->put($rel, File::get($file->getPathname()));
        }
    }

    protected function importSql(string $sql): void
    {
        $driver = Schema::getConnection()->getDriverName();
        // SQLite ignores PRAGMA foreign_keys inside an open transaction (PHPUnit RefreshDatabase).
        // defer_foreign_keys still works and lets INSERT order be alphabetical.
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA defer_foreign_keys = ON');
        }
        Schema::disableForeignKeyConstraints();
        try {
            foreach ($this->backupTables() as $table) {
                DB::table($table)->delete();
            }
            foreach (preg_split('/;\s*\R/', $sql) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                if (preg_match('/^(SET\s|PRAGMA\s|CREATE\s|DROP\s|DELETE\s)/i', $stmt)) {
                    continue;
                }
                DB::unprepared($stmt.';');
            }
        } finally {
            Schema::enableForeignKeyConstraints();
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA defer_foreign_keys = OFF');
            }
        }
    }

    /** @return list<string> */
    protected function backupTables(): array
    {
        return collect(Schema::getTableListing())
            ->map(fn (string $t) => $this->unqualifiedTable($t))
            ->reject(fn (string $t) => in_array($t, self::SKIP_TABLES, true))
            ->unique()
            ->values()
            ->all();
    }

    protected function unqualifiedTable(string $table): string
    {
        $table = str_replace('`', '', $table);

        return str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
    }

    protected function createTableSql(string $table): string
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            $row = DB::selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table]);
            $sql = is_object($row) ? (string) ($row->sql ?? '') : '';

            return $sql !== '' ? rtrim($sql, ';').';' : '';
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
            if (! $row) {
                return '';
            }
            $arr = (array) $row;
            $sql = (string) ($arr['Create Table'] ?? $arr['Create View'] ?? '');

            return $sql !== '' ? rtrim($sql, ';').';' : '';
        }

        return '';
    }

    protected function prune(): void
    {
        $files = glob($this->directory().DIRECTORY_SEPARATOR.'cms-backup-*.zip') ?: [];
        rsort($files);
        foreach (array_slice($files, self::KEEP) as $old) {
            @unlink($old);
        }
    }

    protected function dumpSql(): string
    {
        $tables = $this->backupTables();
        $out = '-- CMS backup '.now()->toDateTimeString()."\nSET FOREIGN_KEY_CHECKS=0;\nPRAGMA foreign_keys=OFF;\n";
        foreach ($tables as $table) {
            $create = $this->createTableSql($table);
            if ($create !== '') {
                $out .= $create."\n";
            }
            $out .= 'DELETE FROM `'.$table."`;\n";
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $data = (array) $row;
                $cols = array_keys($data);
                $vals = array_map(fn ($v) => $this->sqlLiteral($v), array_values($data));
                $out .= 'INSERT INTO `'.$table.'` (`'.implode('`,`', $cols).'`) VALUES ('.implode(',', $vals).");\n";
            }
        }

        return $out."SET FOREIGN_KEY_CHECKS=1;\nPRAGMA foreign_keys=ON;\n";
    }

    protected function sqlLiteral(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }
        if (is_array($v) || is_object($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "''"], (string) $v)."'";
    }
}
