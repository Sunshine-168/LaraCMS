<?php

namespace App\Services\Admin;

use App\Models\Cms\Setting;
use App\Services\Cms\HtmlCacheService;
use App\Support\Cdn;
use App\Support\Result;

class SettingService
{
    public function __construct(private readonly HtmlCacheService $htmlCache) {}

    /** 读取一组设置 */
    public function getMany(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = Setting::getValue($key);
        }

        return $out;
    }

    /** 批量保存设置 */
    public function save(array $pairs): array
    {
        foreach ($pairs as $key => $value) {
            Setting::setValue($key, $value);
        }

        // 站点信息/主题变更影响几乎所有页面
        $this->htmlCache->forgetAll('settings');
        Cdn::applyRuntime();

        return Result::ok($pairs, '设置已保存');
    }
}
