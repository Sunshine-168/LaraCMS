<?php

namespace App\Services\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\Post;
use App\Models\Cms\Setting;
use App\Support\Locale;

class SeoService
{
    /** 组装 SEO 元数据 */
    public function forContent(?object $content = null, ?string $fallbackTitle = null): array
    {
        $site = Setting::getValue('site_name', config('app.name'));
        $title = $fallbackTitle ?: $site;
        $description = (string) Setting::getValue('site_description', '');
        $keywords = (string) Setting::getValue('site_keywords', '');

        if ($content instanceof Page || $content instanceof Post) {
            $title = $content->t('meta_title') ?: ($content->t('title').' - '.$site);
            $description = $content->t('meta_description') ?: ($content->t('excerpt') ?? $description);
        }

        return compact('title', 'description', 'keywords', 'site');
    }

    /** 简易 sitemap URL 列表 */
    public function sitemapUrls(): array
    {
        $urls = [url('/')];
        foreach (Page::query()->published()->get() as $page) {
            $slug = $page->t('slug');
            if ($slug && ! $page->is_homepage) {
                $urls[] = url('/'.$slug);
            }
        }
        foreach (Post::query()->published()->get() as $post) {
            $slug = $post->t('slug');
            if ($slug) {
                $urls[] = url('/posts/'.$slug);
            }
        }

        return $urls;
    }
}
