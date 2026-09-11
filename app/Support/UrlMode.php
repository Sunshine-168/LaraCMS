<?php

namespace App\Support;

use App\Models\Cms\Setting;

/** 前台 URL 模式（伪静态规则库） */
class UrlMode
{
    public const CAT_ENTRY = 'cat_entry';

    public const ENTRY_ID = 'entry_id';

    public const CAT_ID = 'cat_id';

    public const MODEL_CAT_ENTRY = 'model_cat_entry';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::CAT_ENTRY => '/栏目slug/文档slug（默认）',
            self::ENTRY_ID => '/e/{id}',
            self::CAT_ID => '/c/{栏目id}/文档slug',
            self::MODEL_CAT_ENTRY => '/模型slug/栏目slug/文档slug',
        ];
    }

    public static function current(): string
    {
        $mode = (string) Setting::getValue('url_mode', self::CAT_ENTRY);

        return array_key_exists($mode, self::options()) ? $mode : self::CAT_ENTRY;
    }

    public static function localePrefix(?string $locale = null): string
    {
        $locale ??= Locale::current();

        if (! Locale::hideDefault() || $locale !== Locale::default()) {
            return '/'.Locale::toPublic($locale);
        }

        return '';
    }
}
