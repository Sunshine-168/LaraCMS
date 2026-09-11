<?php

namespace App\Services\Cms;

use App\Models\Cms\Setting;
use App\Models\Cms\Tag;
use App\Support\Locale;

/** 正文中标签标题 → /tag/{slug} 自动内链（避免嵌套已有 a 标签） */
class TagAutoLinkService
{
    public function enabled(): bool
    {
        $v = Setting::getValue('tag_autolink', '1');

        return $v === null || $v === '' || (bool) (int) $v;
    }

    public function linkify(?string $html, ?string $locale = null): string
    {
        $html = (string) $html;
        if ($html === '' || ! $this->enabled()) {
            return $html;
        }

        $locale ??= Locale::current();
        $tags = Tag::query()->get()
            ->map(function (Tag $tag) use ($locale) {
                $title = trim((string) $tag->t('title', $locale, ''));
                $slug = trim((string) ($tag->t('slug', $locale, '') ?: $tag->t('slug', Locale::default(), '')));

                return $title !== '' && $slug !== ''
                    ? ['title' => $title, 'slug' => $slug, 'len' => mb_strlen($title)]
                    : null;
            })
            ->filter()
            ->sortByDesc('len')
            ->values();

        if ($tags->isEmpty()) {
            return $html;
        }

        // 保护已有链接与标签，避免二次包裹
        $protected = [];
        $html = preg_replace_callback('/<a\b[^>]*>.*?<\/a>/is', function ($m) use (&$protected) {
            $key = '%%A'.count($protected).'%%';
            $protected[$key] = $m[0];

            return $key;
        }, $html) ?? $html;

        foreach ($tags as $tag) {
            $title = $tag['title'];
            $url = cms_route('tag', ['slug' => $tag['slug']]);
            $pattern = '/(?!<[^>]*)('.preg_quote($title, '/').')(?![^<]*>)/u';
            $replacement = '<a href="'.e($url).'" class="cms-tag-link">$1</a>';
            $html = preg_replace($pattern, $replacement, $html, 1) ?? $html;
        }

        return strtr($html, $protected);
    }
}
