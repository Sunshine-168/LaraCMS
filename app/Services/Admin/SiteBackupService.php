<?php

namespace App\Services\Admin;

use App\Support\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SiteBackupService
{
    private const KEEP = 5;

    /** @var list<string> */
    private const SKIP_TABLES = [
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs',
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
            return Result::fail('需要 PHP ZipArchive 扩展');
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
        $tables = collect(Schema::getTableListing())
            ->reject(fn (string $t) => in_array($t, self::SKIP_TABLES, true))
            ->values();

        $out = '-- CMS backup '.now()->toDateTimeString()."\nSET FOREIGN_KEY_CHECKS=0;\n";
        foreach ($tables as $table) {
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $data = (array) $row;
                $cols = array_keys($data);
                $vals = array_map(fn ($v) => $this->sqlLiteral($v), array_values($data));
                $out .= 'INSERT INTO `'.$table.'` (`'.implode('`,`', $cols).'`) VALUES ('.implode(',', $vals).");\n";
            }
        }

        return $out."SET FOREIGN_KEY_CHECKS=1;\n";
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
