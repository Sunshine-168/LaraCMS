<?php

namespace App\Services\Cms;

use App\Cms\CmsViewContext;
use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Support\CurrentSite;
use App\Support\Locale;
use App\Support\MobileDetect;
use App\Support\ThemeAppearance;
use App\Support\UrlMode;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SiteFrontService
{
    public function __construct(private readonly CmsViewContext $context) {}

    public function bootSite(): array
    {
        $siteModel = CurrentSite::model();
        $pcTheme = (string) ($siteModel?->theme ?: Setting::getValue('theme', config('cms.theme', 'enterprise')));
        $isMobile = false;
        $theme = $pcTheme;
        try {
            $isMobile = MobileDetect::shouldUseMobile(request());
            if ($isMobile && $pcTheme !== 'official' && ($m = MobileDetect::mobileTheme())) {
                $theme = $m;
            }
        } catch (\Throwable) {
            // console / 无 request
        }

        $title = (string) Setting::getValue('site_name', config('app.name'));
        $keyword = (string) Setting::getValue('site_keywords', '');
        $description = (string) Setting::getValue('site_description', '');
        if (Locale::isEnglish() && $title === 'IG包网') {
            $title = 'IG';
            $keyword = 'IG, white-label, game API';
            $description = 'IG. Standard, custom, and the game API.';
        }
        $site = array_merge([
            'title' => $title,
            'keyword' => $keyword,
            'description' => $description,
            'theme' => $theme,
            'pc_theme' => $pcTheme,
            'is_mobile' => $isMobile,
            'analytics_code' => (string) Setting::getValue('analytics_code', ''),
            'url_mode' => UrlMode::current(),
        ], ThemeAppearance::forFront());
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
            $full = "themes.{$theme}.{$tpl}";
            if (view()->exists($full)) {
                return $full;
            }
        }
        foreach ($candidates as $tpl) {
            foreach (['enterprise', 'default'] as $themeName) {
                if ($themeName === $theme) {
                    continue;
                }
                $full = "themes.{$themeName}.{$tpl}";
                if (view()->exists($full)) {
                    return $full;
                }
            }
        }

        return "themes.{$theme}.{$name}";
    }

    /** 当前主题布局（会员/标签等共用页） */
    public function themeLayout(): string
    {
        $theme = config('cms.theme', 'enterprise');
        foreach ([
            "themes.{$theme}.partials.layout",
            "themes.{$theme}.layout",
            'themes.enterprise.partials.layout',
        ] as $view) {
            if (view()->exists($view)) {
                return $view;
            }
        }

        return 'themes.enterprise.partials.layout';
    }

    /**
     * 优先当前主题模板，其次 cms.* 共用页，避免落到别的主题布局。
     */
    public function pageView(string $name, ?string $fallback = null): string
    {
        $theme = config('cms.theme', 'enterprise');
        $candidates = array_values(array_unique(array_filter([$name, $fallback])));

        foreach ($candidates as $tpl) {
            $full = "themes.{$theme}.{$tpl}";
            if (view()->exists($full)) {
                return $full;
            }
        }
        foreach ($candidates as $tpl) {
            $cms = 'cms.'.$tpl;
            if (view()->exists($cms)) {
                return $cms;
            }
        }

        return $this->themeView($name, $fallback);
    }

    public function renderThemePage(string $name, array $data = [], ?string $fallback = null): View
    {
        $data['layout'] = $data['layout'] ?? $this->themeLayout();

        return view($this->pageView($name, $fallback), $data);
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
        return app(SitemapService::class)->xmlItems();
    }
}
