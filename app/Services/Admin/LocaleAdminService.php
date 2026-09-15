<?php

namespace App\Services\Admin;

use App\Models\Cms\CmsLocale;
use App\Support\Locale;
use App\Support\Result;
use Illuminate\Support\Collection;

class LocaleAdminService
{
    public function all(): Collection
    {
        return CmsLocale::query()->orderBy('sort')->orderBy('id')->get();
    }

    /** @param  array<string, mixed>  $input */
    public function save(?CmsLocale $locale, array $input): array
    {
        $locale ??= new CmsLocale;
        $creating = ! $locale->exists;

        $label = trim((string) ($input['label'] ?? $locale->label ?? ''));
        if ($label === '') {
            return Result::fail('请填写名称');
        }

        $code = Locale::normalize(trim((string) ($input['code'] ?? $locale->code ?? '')));
        if ($code === '') {
            return Result::fail('请填写语言代码，如 en、ja');
        }
        if (! preg_match('/^[a-z]{2}(_[a-z]{2})?$/', $code)) {
            return Result::fail('代码用 en、ja、zh_cn 这种写法');
        }
        $taken = CmsLocale::query()
            ->where('code', $code)
            ->when($locale->exists, fn ($q) => $q->where('id', '!=', $locale->id))
            ->exists();
        if ($taken) {
            return Result::fail('这个代码已经有了');
        }

        $locale->code = $code;
        $locale->label = $label;
        $native = trim((string) ($input['native'] ?? ''));
        if ($native !== '' || $creating || ! empty($input['from_form'])) {
            $locale->native = $native !== '' ? $native : $label;
        } elseif (trim((string) $locale->native) === '') {
            $locale->native = $label;
        }

        if (! empty($input['from_form'])) {
            $locale->sort = max(0, (int) ($input['sort'] ?? $locale->sort ?? 0));
        } elseif ($creating) {
            $locale->sort = (int) CmsLocale::query()->max('sort') + 10;
        }

        if (array_key_exists('is_active', $input) || ! empty($input['from_form'])) {
            $locale->is_active = ! empty($input['is_active']);
        } elseif ($creating) {
            $locale->is_active = true;
        }

        $makeDefault = array_key_exists('is_default', $input)
            ? ! empty($input['is_default'])
            : (bool) $locale->is_default;
        if ($creating && ! CmsLocale::query()->where('is_default', true)->exists()) {
            $makeDefault = true;
        }

        if ($makeDefault || $locale->is_default) {
            $locale->is_active = true;
        }

        if ($locale->is_default || $makeDefault) {
            if (array_key_exists('hide_default', $input)) {
                $locale->hide_default = ! empty($input['hide_default']);
            } else {
                $prev = CmsLocale::query()
                    ->where('is_default', true)
                    ->when($locale->exists, fn ($q) => $q->where('id', '!=', $locale->id))
                    ->first();
                $locale->hide_default = $prev ? (bool) $prev->hide_default : true;
            }
        } elseif ($creating) {
            $locale->hide_default = true;
        }

        $locale->is_default = $makeDefault;
        $locale->save();

        if ($locale->is_default) {
            CmsLocale::query()->where('id', '!=', $locale->id)->update(['is_default' => false]);
        }
        Locale::forgetCache();

        return Result::ok($locale, $creating ? '已添加' : '已保存');
    }

    public function toggle(CmsLocale $locale): array
    {
        if ($locale->is_default && $locale->is_active) {
            return Result::fail('默认语言不能停用');
        }
        $locale->is_active = ! $locale->is_active;
        $locale->save();
        Locale::forgetCache();

        return Result::ok(
            $locale,
            $locale->is_active ? '已启用，前台可以切这种语言' : '已停用，前台暂时不提供这种语言'
        );
    }

    public function makeDefault(CmsLocale $locale): array
    {
        if (! $locale->is_active) {
            return Result::fail('请先启用这种语言');
        }
        $prev = CmsLocale::query()->where('is_default', true)->where('id', '!=', $locale->id)->first();
        if ($prev) {
            $locale->hide_default = $prev->hide_default;
        }
        $locale->is_default = true;
        $locale->is_active = true;
        $locale->save();
        CmsLocale::query()->where('id', '!=', $locale->id)->update(['is_default' => false]);
        Locale::forgetCache();

        return Result::ok($locale, '已设为默认语言');
    }

    public function delete(CmsLocale $locale): array
    {
        if ($locale->is_default) {
            return Result::fail('默认语言不能删除');
        }
        $name = $locale->label ?: $locale->code;
        $locale->delete();
        Locale::forgetCache();

        return Result::ok(null, '已删除「'.$name.'」');
    }
}
