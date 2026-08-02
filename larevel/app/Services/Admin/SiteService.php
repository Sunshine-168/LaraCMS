<?php

namespace App\Services\Admin;

use App\Models\Cms\Site;
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
        $site->is_active = ! empty($input['is_active']);
        $site->is_default = ! empty($input['is_default']);
        $theme = trim((string) ($input['theme'] ?? ''));
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

        return Result::ok($site, $isNew ? '站点已创建（已复制基础设置）' : '站点已保存');
    }

    public function delete(Site $site): array
    {
        if ($site->is_default) {
            return Result::fail('不能删除默认站点');
        }
        if (Site::query()->count() <= 1) {
            return Result::fail('至少保留一个站点');
        }
        $site->delete();

        return Result::ok(null, '站点已删除（相关内容已级联删除）');
    }
}
