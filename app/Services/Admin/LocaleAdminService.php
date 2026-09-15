<?php

namespace App\Services\Admin;

use App\Models\Cms\CmsLocale;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Services\Cms\DiskHtmlService;
use App\Services\Cms\HtmlCacheService;
use App\Support\AdminUi;
use App\Support\Locale;
use App\Support\Result;
use App\Support\ThemeUi;
use Illuminate\Support\Collection;

class LocaleAdminService
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly DiskHtmlService $diskHtml,
    ) {}

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
            return Result::fail($this->msg('err_label'));
        }

        if ($locale->exists) {
            $code = (string) $locale->code;
        } else {
            $code = Locale::normalize(trim((string) ($input['code'] ?? '')));
            if ($code === '') {
                return Result::fail($this->msg('err_code'));
            }
            if (! preg_match('/^[a-z]{2}(_[a-z]{2})?$/', $code)) {
                return Result::fail($this->msg('err_code_format'));
            }
            $taken = CmsLocale::query()->where('code', $code)->exists();
            if ($taken) {
                return Result::fail($this->msg('err_code_taken'));
            }
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
        $this->bustFront();

        return Result::ok($locale, $creating ? $this->msg('msg_added') : $this->msg('msg_saved'));
    }

    public function toggle(CmsLocale $locale): array
    {
        if ($locale->is_default && $locale->is_active) {
            return Result::fail($this->msg('msg_default_locked'));
        }
        $locale->is_active = ! $locale->is_active;
        $locale->save();
        $this->bustFront();

        return Result::ok(
            $locale,
            $locale->is_active ? $this->msg('msg_on') : $this->msg('msg_off')
        );
    }

    public function makeDefault(CmsLocale $locale): array
    {
        if (! $locale->is_active) {
            return Result::fail($this->msg('msg_need_enable'));
        }
        $prev = CmsLocale::query()->where('is_default', true)->where('id', '!=', $locale->id)->first();
        if ($prev) {
            $locale->hide_default = $prev->hide_default;
        }
        $locale->is_default = true;
        $locale->is_active = true;
        $locale->save();
        CmsLocale::query()->where('id', '!=', $locale->id)->update(['is_default' => false]);
        $this->bustFront();

        return Result::ok($locale, $this->msg('msg_made_default'));
    }

    public function delete(CmsLocale $locale): array
    {
        if ($locale->is_default) {
            return Result::fail($this->msg('msg_cannot_delete'));
        }
        $name = $locale->label ?: $locale->code;
        $locale->delete();
        $this->bustFront();

        return Result::ok(null, $this->msg('msg_deleted', ['name' => $name]));
    }

    /** @return array<string, array{total: int, filled: int}> */
    public function coverage(): array
    {
        $total = Entry::query()->count();
        $out = [];
        foreach (CmsLocale::query()->orderBy('sort')->orderBy('id')->get() as $row) {
            $filled = 0;
            if ($total > 0) {
                Entry::query()->select(['id', 'title'])->orderBy('id')->chunkById(200, function ($rows) use (&$filled, $row) {
                    foreach ($rows as $entry) {
                        if ($entry->translation('title', $row->code) !== '') {
                            $filled++;
                        }
                    }
                }, 'id');
            }
            $out[$row->code] = ['total' => $total, 'filled' => $filled];
        }

        return $out;
    }

    public function saveAdminUi(string $code, bool $forUser = true): array
    {
        if (! AdminUi::isValid($code)) {
            return Result::fail($this->msg('err_ui'));
        }
        if ($forUser) {
            $remembered = AdminUi::remember($code, true);
            AdminUi::apply();

            return Result::ok($remembered, $this->msg('msg_admin'));
        }
        Setting::setValue('admin_ui_locale', $code);

        return Result::ok(null, $code === AdminUi::EN ? $this->msg('msg_site_en') : $this->msg('msg_site_zh'));
    }

    /** @param  array<string, array<string, string>>  $input */
    public function saveThemeUi(array $input): array
    {
        ThemeUi::save($input);
        $this->bustFront('theme-ui', false);

        return Result::ok(null, $this->msg('msg_theme'));
    }

    private function bustFront(string $reason = 'locales', bool $localeCache = true): void
    {
        if ($localeCache) {
            Locale::forgetCache();
        }
        $this->htmlCache->forgetAll($reason);
        if ($this->diskHtml->enabled()) {
            $this->diskHtml->clear();
        }
    }

    /** @param  array<string, string>  $replace */
    private function msg(string $key, array $replace = []): string
    {
        return trans('admin.locale.'.$key, $replace);
    }
}
