<?php

namespace App\Support;

use App\Models\Cms\Setting;
use App\Services\Cms\ThemeCatalog;

/** 某一套主题的自定义参数（theme.json 的 config + 后台填的值） */
class ThemeVars
{
    /**
     * @return list<array{key:string,group:string,title:string,type:string,tips:string,value:string,options:list<array{value:string,label:string}>}>
     */
    public static function schema(string $slug): array
    {
        $byKey = [];
        foreach (self::defaults() as $key => $field) {
            self::putField($byKey, $key, $field, '');
        }
        $json = app(ThemeCatalog::class)->manifest($slug);
        $config = $json['config'] ?? null;
        if (is_array($config)) {
            foreach ($config as $key => $field) {
                if (! is_string($key) || ! is_array($field)) {
                    continue;
                }
                if (isset($field['item']) && is_array($field['item'])) {
                    $group = trim((string) ($field['title'] ?? $key));
                    foreach ($field['item'] as $innerKey => $inner) {
                        if (is_string($innerKey) && is_array($inner)) {
                            self::putField($byKey, $key.'.'.$innerKey, $inner, $group);
                        }
                    }
                    continue;
                }
                self::putField($byKey, $key, $field, '');
            }
        }

        return array_values($byKey);
    }

    /** @return array<string, string> */
    public static function stored(string $slug): array
    {
        try {
            $raw = Setting::getValue(self::settingKey($slug), '');
        } catch (\Throwable) {
            return [];
        }
        if (is_array($raw)) {
            return self::stringify($raw);
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? self::stringify($decoded) : [];
    }

    /** @return array<string, string> */
    public static function all(?string $slug = null): array
    {
        $slug = self::resolveSlug($slug);
        $stored = self::stored($slug);
        $out = [];
        foreach (self::schema($slug) as $field) {
            $value = $stored[$field['key']] ?? $field['value'];
            $out[$field['key']] = (string) $value;
        }

        return $out;
    }

    public static function get(string $key, ?string $slug = null): string
    {
        $all = self::all($slug);

        return trim((string) ($all[$key] ?? ''));
    }

    /** @param  array<string, mixed>  $input */
    public static function save(string $slug, array $input): void
    {
        $prev = self::stored($slug);
        $out = [];
        foreach (self::schema($slug) as $field) {
            $key = $field['key'];
            if ($field['type'] === 'checkbox') {
                $out[$key] = ! empty($input[$key]) ? '1' : '0';
                continue;
            }
            if (! array_key_exists($key, $input)) {
                $out[$key] = $prev[$key] ?? $field['value'];
                continue;
            }
            $value = trim((string) $input[$key]);
            $limit = $field['type'] === 'textarea' ? 2000 : 200;
            $out[$key] = mb_substr($value, 0, $limit);
        }
        Setting::setValue(self::settingKey($slug), json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function settingKey(string $slug): string
    {
        return 'theme_vars.'.$slug;
    }

    protected static function resolveSlug(?string $slug): string
    {
        $slug = trim((string) $slug);
        if ($slug !== '') {
            return $slug;
        }
        $fromConfig = trim((string) config('cms.theme', ''));
        if ($fromConfig !== '') {
            return $fromConfig;
        }

        return app(ThemeCatalog::class)->currentSlug();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function defaults(): array
    {
        return [
            'home_notice' => [
                'title' => '首页公告',
                'type' => 'textarea',
                'tips' => '显示在页头下面。留空则不显示。',
                'value' => '',
            ],
            'home_title' => [
                'title' => '首页主标题',
                'type' => 'text',
                'tips' => '设置里的首页标题为空时用这里。再空则用主题自带文案。',
                'value' => '',
            ],
            'home_sub' => [
                'title' => '首页副标题',
                'type' => 'text',
                'value' => '',
            ],
            'cta_text' => [
                'title' => '首页按钮文字',
                'type' => 'text',
                'tips' => '配合首页主标题。留空则不显示按钮。',
                'value' => '',
            ],
            'cta_url' => [
                'title' => '首页按钮链接',
                'type' => 'text',
                'tips' => '可以是 /about 或完整网址。',
                'value' => '/',
            ],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $field
     */
    protected static function putField(array &$fields, string $key, array $field, string $group): void
    {
        $key = trim($key);
        if (! preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)?$/', $key)) {
            return;
        }
        $type = strtolower((string) ($field['type'] ?? 'text'));
        if (! in_array($type, ['text', 'textarea', 'select', 'checkbox', 'color'], true)) {
            $type = 'text';
        }
        $fields[$key] = [
            'key' => $key,
            'group' => $group,
            'title' => trim((string) ($field['title'] ?? $key)) ?: $key,
            'type' => $type,
            'tips' => trim((string) ($field['tips'] ?? '')),
            'value' => (string) ($field['value'] ?? ($type === 'checkbox' ? '0' : '')),
            'options' => self::options($field['options'] ?? ($field['choices'] ?? [])),
        ];
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    protected static function options(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $key => $row) {
            if (is_array($row)) {
                $value = trim((string) ($row['value'] ?? $row['key'] ?? ''));
                $label = trim((string) ($row['label'] ?? $row['title'] ?? $value));
            } elseif (is_int($key)) {
                $value = trim((string) $row);
                $label = $value;
            } else {
                $value = trim((string) $key);
                $label = trim((string) $row);
            }
            if ($value !== '') {
                $out[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
            }
        }

        return $out;
    }

    /**
     * @param  array<mixed>  $raw
     * @return array<string, string>
     */
    protected static function stringify(array $raw): array
    {
        $out = [];
        foreach ($raw as $key => $value) {
            if (is_string($key)) {
                $out[$key] = is_scalar($value) || $value === null ? (string) $value : '';
            }
        }

        return $out;
    }
}
