<?php

namespace App\Services\Cms;

use App\Cms\CmsViewContext;
use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Support\CurrentSite;
use App\Support\Locale;
use App\Support\MobileDetect;
use Illuminate\Support\Collection;

class SiteFrontService
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    public function bootSite(): array
    {
        $siteModel = CurrentSite::model();
        $pcTheme = (string) ($siteModel?->theme ?: Setting::getValue('theme', config('cms.theme', 'enterprise')));
        $isMobile = false;
        $theme = $pcTheme;
        try {
            $isMobile = MobileDetect::shouldUseMobile(request());
            if ($isMobile && ($m = MobileDetect::mobileTheme())) {
                $theme = $m;
            }
        } catch (\Throwable) {
            // console / 无 request
        }

        $site = [
            'title' => (string) Setting::getValue('site_name', config('app.name')),
            'keyword' => (string) Setting::getValue('site_keywords', ''),
            'description' => (string) Setting::getValue('site_description', ''),
            'theme' => $theme,
            'pc_theme' => $pcTheme,
            'is_mobile' => $isMobile,
            'analytics_code' => (string) Setting::getValue('analytics_code', ''),
            'url_mode' => \App\Support\UrlMode::current(),
        ];
        $this->context->setSite($site);
        config([
            'cms.theme' => $theme,
            'cms.is_mobile' => $isMobile,
        ]);

        return $site;
    }

    public function homepageEntry(): ?Entry
    {
        return Entry::query()->published()->homepage()->first()
            ?: Entry::query()->published()
                ->whereHas('modelDef', fn ($q) => $q->where('kind', 'single'))
                ->orderBy('sort_order')
                ->first();
    }

    public function categoryBySlug(string $slug): ?Category
    {
        $locale = Locale::current();

        return Category::query()->active()->with('modelDef')->get()
            ->first(function (Category $c) use ($slug, $locale) {
                return ($c->slug[$locale] ?? null) === $slug
                    || ($c->slug[Locale::default()] ?? null) === $slug;
            });
    }

    public function entryBySlug(Category $category, string $slug): ?Entry
    {
        $locale = Locale::current();

        return Entry::query()->with(['category', 'modelDef', 'tags'])
            ->published()
            ->inCategories([(int) $category->id])
            ->get()
            ->first(function (Entry $e) use ($slug, $locale) {
                return ($e->slug[$locale] ?? null) === $slug
                    || ($e->slug[Locale::default()] ?? null) === $slug;
            });
    }

    public function singleEntry(Category $category): ?Entry
    {
        return Entry::query()->published()
            ->inCategories([(int) $category->id])
            ->orderBy('sort_order')
            ->first();
    }

    public function themeView(string $name, ?string $fallback = null): string
    {
        $theme = config('cms.theme', 'enterprise');
        $candidates = array_values(array_unique(array_filter([
            $name,
            $fallback,
        ])));

        foreach ($candidates as $tpl) {
            foreach ([$theme, 'enterprise', 'default'] as $themeName) {
                $full = "themes.{$themeName}.{$tpl}";
                if (view()->exists($full)) {
                    return $full;
                }
            }
        }

        return "themes.{$theme}.{$name}";
    }

    public function resolveListTpl(Category $category): string
    {
        $tpl = $category->list_tpl
            ?: $category->modelDef?->list_tpl
            ?: 'list.list';

        return $this->themeView($tpl, 'list.list');
    }

    public function resolveShowTpl(Category $category, ?Entry $entry = null): string
    {
        $tpl = $entry?->attr('show_tpl')
            ?: $category->show_tpl
            ?: $category->modelDef?->show_tpl
            ?: 'show.show';

        return $this->themeView($tpl, 'show.show');
    }

    public function resolveCategoryTpl(Category $category): string
    {
        $tpl = $category->category_tpl ?: 'category.category';

        return $this->themeView($tpl, 'category.category');
    }

    public function sitemapUrls(): Collection
    {
        $urls = collect([url('/')]);
        foreach (Category::query()->active()->get() as $cat) {
            if ($cat->type !== 'link') {
                $urls->push(url($cat->frontUrl()));
            }
        }
        foreach (Entry::query()->published()->with('category')->get() as $entry) {
            if ($entry->modelDef?->kind !== 'single' && $entry->modelDef?->slug !== 'link') {
                $urls->push(url($entry->url));
            }
        }

        return $urls->unique()->values();
    }
}
