<?php

namespace App\Services\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\Post;
use App\Support\Locale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FrontendService
{
    /** 获取首页 */
    public function homepage(): ?Page
    {
        return Page::query()->published()->homepage()->first()
            ?: Page::query()->published()->orderBy('sort_order')->first();
    }

    /** 按 slug 取页面 */
    public function pageBySlug(string $slug, ?string $locale = null): ?Page
    {
        $locale = Locale::normalize($locale ?: Locale::current());

        return Page::query()->published()->get()->first(function (Page $page) use ($slug, $locale) {
            return ($page->slug[$locale] ?? null) === $slug
                || ($page->slug[Locale::default()] ?? null) === $slug;
        });
    }

    /** 文章分页 */
    public function posts(int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()->with('category')->published()->orderByDesc('published_at')->paginate($perPage);
    }

    /** 按 slug 取文章 */
    public function postBySlug(string $slug, ?string $locale = null): ?Post
    {
        $locale = Locale::normalize($locale ?: Locale::current());

        return Post::query()->with('category')->published()->get()->first(function (Post $post) use ($slug, $locale) {
            return ($post->slug[$locale] ?? null) === $slug
                || ($post->slug[Locale::default()] ?? null) === $slug;
        });
    }
}
