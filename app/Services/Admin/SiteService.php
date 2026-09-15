<?php

namespace App\Services\Admin;

use App\Models\Cms\Site;
use App\Services\Cms\ThemeCatalog;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SiteService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Site::query()->orderByDesc('is_default')->orderBy('id')->paginate($perPage);
    }

    public function save(?Site $site, array $input): array
    {
        $site ??= new Site;
        $isNew = ! $site->exists;

        $domain = Site::normalizeDomain((string) ($input['domain'] ?? $site->domain));
        $exists = Site::query()
            ->where('domain', $domain)
            ->when($site->exists, fn ($q) => $q->where('id', '!=', $site->id))
            ->exists();
        if ($exists) {
            return Result::fail('该域名已被其他站点使用');
        }

        $site->name = trim((string) ($input['name'] ?? $site->name));
        $site->domain = $domain;
        $site->is_active = array_key_exists('is_active', $input)
            ? ! empty($input['is_active'])
            : ($site->exists ? (bool) $site->is_active : true);
        $site->is_default = array_key_exists('is_default', $input)
            ? ! empty($input['is_default'])
            : ($site->exists ? (bool) $site->is_default : false);
        $theme = trim((string) ($input['theme'] ?? ''));
        if ($theme !== '' && ! app(ThemeCatalog::class)->exists($theme)) {
            return Result::fail('没有这套主题');
        }
        $site->theme = $theme !== '' ? $theme : null;
        $site->remark = trim((string) ($input['remark'] ?? '')) ?: null;

        if ($site->name === '') {
            return Result::fail('请填写站点名称');
        }

        DB::transaction(function () use ($site, $isNew) {
            $site->save();
            if ($isNew) {
                $source = Site::query()->where('is_default', true)->where('id', '!=', $site->id)->first()
                    ?: Site::query()->where('id', '!=', $site->id)->orderBy('id')->first();
                if ($source) {
                    $site->copyBasicsFrom($source);
                }
            }
            if (! Site::query()->where('is_default', true)->exists()) {
                $site->is_default = true;
                $site->save();
            }
        });

        return Result::ok($site, $isNew ? '站点已添加。栏目和文章是空的，可在顶栏切过去接着改。' : '站点已保存');
    }

    public function toggle(Site $site): array
    {
        if ($site->is_default && $site->is_active) {
            return Result::fail('默认站点不能停用');
        }
        $site->is_active = ! $site->is_active;
        $site->save();

        return Result::ok(
            $site,
            $site->is_active ? '已启用，这个域名可以打开' : '已停用，这个域名暂时打不开'
        );
    }

    public function makeDefault(Site $site): array
    {
        if (! $site->is_active) {
            return Result::fail('请先启用这个站点');
        }
        $site->is_default = true;
        $site->save();

        return Result::ok($site, '已设为默认站点');
    }

    public function delete(Site $site): array
    {
        if ($site->is_default) {
            return Result::fail('默认站点不能删除');
        }
        if (Site::query()->count() <= 1) {
            return Result::fail('至少保留一个站点');
        }
        $site->delete();

        return Result::ok(null, '已删除这个站点');
    }
}
