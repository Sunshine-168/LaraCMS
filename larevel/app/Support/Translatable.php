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

    public function setTranslation(string $field, string $locale, mixed $text): void
    {
        $value = $this->{$field};
        if (! is_array($value)) {
            $value = [];
        }
        $value[Locale::normalize($locale)] = $text;
        $this->{$field} = $value;
    }

    /** 从表单 locales[zh_cn][title] 合并 */
    public static function packTranslations(array $input, array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $bag = [];
            foreach (Locale::codes() as $code) {
                if (isset($input[$code][$field])) {
                    $bag[$code] = $input[$code][$field];
                }
            }
            $out[$field] = $bag;
        }

        return $out;
    }
}
