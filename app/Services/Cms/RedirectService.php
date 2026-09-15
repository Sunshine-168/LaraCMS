<?php

namespace App\Services\Cms;

use App\Models\Cms\Redirect;
use App\Support\CurrentSite;
use App\Support\Result;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedirectService
{
    /** 规范化路径：以 / 开头、去掉末尾 /（根路径除外）、去掉 query/fragment */
    public static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        if (preg_match('#^https?://#i', $path)) {
            $parsed = parse_url($path);
            $path = ($parsed['path'] ?? '/') ?: '/';
        }
        $path = '/'.ltrim($path, '/');
        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    public function mapForSite(?int $siteId = null): array
    {
        $siteId ??= CurrentSite::idOrDefault();
        if (! $siteId) {
            return [];
        }

        return Cache::remember(Redirect::cacheKey($siteId), 3600, function () use ($siteId) {
            return Redirect::query()->withoutGlobalScopes()
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->get(['id', 'source_path', 'target_url', 'status_code'])
                ->mapWithKeys(fn (Redirect $r) => [
                    $r->source_path => [
                        'id' => (int) $r->id,
                        'target' => $r->target_url,
                        'code' => (int) $r->status_code,
                    ],
                ])
                ->all();
        });
    }

    /** @return array{id:int,target:string,code:int}|null */
    public function match(string $path, ?int $siteId = null): ?array
    {
        $path = self::normalizePath($path);
        $map = $this->mapForSite($siteId);

        return $map[$path] ?? null;
    }

    public function recordHit(int $id): void
    {
        DB::table('cms_redirects')->where('id', $id)->update([
            'hits' => DB::raw('hits + 1'),
            'last_hit_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function save(?Redirect $redirect, array $input): array
    {
        $redirect ??= new Redirect;
        $source = self::normalizePath((string) ($input['source_path'] ?? ''));
        $target = trim((string) ($input['target_url'] ?? ''));
        $code = (int) ($input['status_code'] ?? 301);
        if (! in_array($code, [301, 302], true)) {
            $code = 301;
        }
        if ($source === '' || $source === '/') {
            return Result::fail('源路径不能为空或仅为 /');
        }
        if ($target === '') {
            return Result::fail('请填写目标地址');
        }

        $targetPath = self::normalizePath($target);
        if (! preg_match('#^https?://#i', $target) && $targetPath === $source) {
            return Result::fail('目标不能与源路径相同（防自环）');
        }

        // 简单防双向环：目标若为本站路径，且已有反向规则
        if (! preg_match('#^https?://#i', $target)) {
            $back = Redirect::query()
                ->where('source_path', $targetPath)
                ->when($redirect->exists, fn ($q) => $q->where('id', '!=', $redirect->id))
                ->where('is_active', true)
                ->first();
            if ($back && self::normalizePath($back->target_url) === $source) {
                return Result::fail('与已有规则形成重定向环：'.$targetPath.' ↔ '.$source);
            }
        }

        $exists = Redirect::query()
            ->where('source_path', $source)
            ->when($redirect->exists, fn ($q) => $q->where('id', '!=', $redirect->id))
            ->exists();
        if ($exists) {
            return Result::fail('该源路径已存在规则');
        }

        $redirect->fill([
            'source_path' => $source,
            'target_url' => $target,
            'status_code' => $code,
            'is_active' => array_key_exists('is_active', $input)
                ? ! empty($input['is_active'])
                : ($redirect->exists ? (bool) $redirect->is_active : true),
            'note' => trim((string) ($input['note'] ?? '')) ?: null,
        ]);
        $redirect->save();

        return Result::ok($redirect, '这条跳转已保存');
    }

    public function delete(Redirect $redirect): array
    {
        $redirect->delete();

        return Result::ok(null, '已删除');
    }

    public function resolveTargetUrl(string $target): string
    {
        if (preg_match('#^https?://#i', $target)) {
            return $target;
        }

        return url(self::normalizePath($target));
    }
}
