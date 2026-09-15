<?php

namespace App\Support;

/** JSON 多语言字段读写 */
trait Translatable
{
    public function t(string $field, ?string $locale = null, mixed $default = ''): mixed
    {
        $locale = $locale ?: Locale::current();
        $value = $this->{$field};

        if (is_array($value)) {
            return $value[$locale] ?? $value[Locale::default()] ?? $default;
        }

        return $value ?? $default;
    }

    public function translation(string $field, string $locale): string
    {
        $value = $this->{$field};
        if (is_array($value)) {
            return trim((string) ($value[$locale] ?? ''));
        }

        return '';
    }

    /** 从表单 locales[zh_cn][title] 合并；未出现在表单里的语言（例如已停用）原样保留 */
    public static function packTranslations(array $input, array $fields, array $existing = []): array
    {
        $out = [];
        foreach ($fields as $field) {
            $bag = [];
            if (isset($existing[$field]) && is_array($existing[$field])) {
                $bag = $existing[$field];
            }
            foreach ($input as $code => $row) {
                if (! is_array($row) || ! array_key_exists($field, $row)) {
                    continue;
                }
                $bag[(string) $code] = $row[$field];
            }
            $out[$field] = $bag;
        }

        return $out;
    }

    /** @param  array<int, string>  $fields */
    public static function existingTranslations(?object $model, array $fields): array
    {
        if (! $model || ! ($model->exists ?? false)) {
            return [];
        }
        $out = [];
        foreach ($fields as $field) {
            $value = $model->{$field};
            $out[$field] = is_array($value) ? $value : [];
        }

        return $out;
    }

    /** @param  array<string, mixed>  $row */
    public static function packKeyedLocales(array $row, string $prefix, bool $fillEmpty = true): array
    {
        $codes = Locale::configuredCodes();
        if ($codes === []) {
            $codes = Locale::codes();
        }
        $bag = [];
        foreach ($codes as $code) {
            $bag[$code] = trim((string) ($row[$prefix.'_'.$code] ?? ''));
        }
        if (! $fillEmpty) {
            return $bag;
        }
        $filled = array_values(array_filter($bag, fn ($v) => $v !== ''));
        if ($filled === []) {
            return $bag;
        }
        $fallback = ($bag[Locale::default()] ?? '') !== '' ? $bag[Locale::default()] : $filled[0];
        foreach ($bag as $code => $value) {
            if ($value === '') {
                $bag[$code] = $fallback;
            }
        }

        return $bag;
    }

    /** @param  array<string, mixed>  $row */
    public static function keyedLocalesHaveText(array $row, string $prefix): bool
    {
        foreach (array_merge(Locale::configuredCodes(), Locale::codes()) as $code) {
            if (trim((string) ($row[$prefix.'_'.$code] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    public function setTranslation(string $field, string $locale, mixed $text): void
    {
        $value = $this->{$field};
        if (! is_array($value)) {
            $value = [];
        }
        $value[Locale::normalize($locale)] = $text;
        $this->{$field} = $value;
    }
}
