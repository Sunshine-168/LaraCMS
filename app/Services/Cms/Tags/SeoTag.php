<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Support\ThemeAppearance;
use Illuminate\Support\HtmlString;

class SeoTag
{
    public function __construct(private readonly CmsViewContext $context) {}

    public function render(): HtmlString
    {
        $title = $this->context->pageTitle();
        $keywords = $this->context->seoKeywords();
        $description = $this->context->seoDescription();
        $site = $this->context->site();
        $siteName = (string) ($site['title'] ?? config('app.name'));
        $canonical = $this->abs(url()->current());
        $article = $this->context->article();
        $category = $this->context->category();
        $image = $this->shareImage($article);
        $ogType = $article ? 'article' : 'website';
        $robots = $this->robotsMeta();

        $html = '<title>'.e($title)."</title>\n"
            .'<meta name="csrf-token" content="'.e(csrf_token())."\">\n"
            .'<meta name="keywords" content="'.e($keywords)."\">\n"
            .'<meta name="description" content="'.e($description)."\">\n"
            .'<link rel="canonical" href="'.e($canonical)."\">\n"
            .$this->hreflangLinks()
            .'<meta property="og:locale" content="'.e($this->ogLocale())."\">\n"
            .$this->ogLocaleAlternates()
            .'<meta property="og:type" content="'.e($ogType)."\">\n"
            .'<meta property="og:site_name" content="'.e($siteName)."\">\n"
            .'<meta property="og:title" content="'.e($title)."\">\n"
            .'<meta property="og:description" content="'.e($description)."\">\n"
            .'<meta property="og:url" content="'.e($canonical)."\">\n"
            .'<meta name="twitter:card" content="'.($image !== '' ? 'summary_large_image' : 'summary')."\">\n"
            .'<meta name="twitter:title" content="'.e($title)."\">\n"
            .'<meta name="twitter:description" content="'.e($description).'">';
        if ($image !== '') {
            $html .= "\n".'<meta property="og:image" content="'.e($image).'">'
                ."\n".'<meta name="twitter:image" content="'.e($image).'">';
        }
        if ($robots !== '') {
            $html .= "\n".'<meta name="robots" content="'.e($robots).'">';
        }
        $jsonLd = $this->jsonLd($title, $description, $canonical, $siteName, $image, $article, $category);
        if ($jsonLd !== []) {
            $payload = json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
            $html .= "\n".'<script type="application/ld+json">'.$payload.'</script>';
        }

        return new HtmlString($html);
    }

    protected function robotsMeta(): string
    {
        if ($this->context->isPreview() || $this->context->noIndex()) {
            return 'noindex,nofollow';
        }
        $path = '/'.ltrim((string) request()->path(), '/');
        if ($path === '/search' || str_ends_with($path, '/search')) {
            return 'noindex,follow';
        }

        return '';
    }

    protected function shareImage($article): string
    {
        $cover = $article ? trim((string) $article->cover) : '';
        if ($cover !== '') {
            return $this->abs(ThemeAppearance::mediaUrl($cover));
        }
        $logo = (string) ($this->context->site()['logo_url'] ?? '');

        return $this->abs($logo);
    }

    protected function hreflangLinks(): string
    {
        $html = '';
        $codes = \App\Support\Locale::codes();
        if (count($codes) < 2) {
            return $html;
        }
        foreach ($codes as $code) {
            $lang = $code === 'zh_cn' ? 'zh-CN' : \App\Support\Locale::toPublic($code);
            $html .= '<link rel="alternate" hreflang="'.e($lang).'" href="'.e($this->abs(cms_locale_url($code)))."\">\n";
        }
        $default = \App\Support\Locale::default();
        $html .= '<link rel="alternate" hreflang="x-default" href="'.e($this->abs(cms_locale_url($default)))."\">\n";

        return $html;
    }

    protected function ogLocale(): string
    {
        return $this->ogLocaleFor(\App\Support\Locale::current());
    }

    protected function ogLocaleFor(string $code): string
    {
        return $code === 'zh_cn' ? 'zh_CN' : ($code === 'en' ? 'en_US' : str_replace('-', '_', \App\Support\Locale::toPublic($code)));
    }

    protected function ogLocaleAlternates(): string
    {
        $html = '';
        $current = \App\Support\Locale::current();
        foreach (\App\Support\Locale::codes() as $code) {
            if ($code === $current) {
                continue;
            }
            $html .= '<meta property="og:locale:alternate" content="'.e($this->ogLocaleFor($code))."\">\n";
        }

        return $html;
    }

    protected function isFrontHome(string $path): bool
    {
        if ($path === '/') {
            return true;
        }
        foreach (\App\Support\Locale::codes() as $code) {
            if ($path === '/'.\App\Support\Locale::toPublic($code)) {
                return true;
            }
        }

        return false;
    }

    protected function abs(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '//')) {
            return (request()->isSecure() ? 'https:' : 'http:').$path;
        }

        return url($path);
    }

    /** @return array<string, mixed> */
    protected function jsonLd(
        string $title,
        string $description,
        string $canonical,
        string $siteName,
        string $image,
        $article,
        $category,
    ): array {
        if ($article) {
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $article->t('title') ?: $title,
                'description' => $description,
                'mainEntityOfPage' => $canonical,
                'dateModified' => optional($article->updated_at)?->toIso8601String(),
            ];
            if ($article->published_at) {
                $data['datePublished'] = $article->published_at->toIso8601String();
            }
            if ($image !== '') {
                $data['image'] = [$image];
            }

            return array_filter($data, fn ($v) => $v !== null && $v !== '');
        }
        if ($category) {
            return array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $category->t('title') ?: $title,
                'description' => $description,
                'url' => $canonical,
            ]);
        }
        $path = '/'.ltrim((string) request()->path(), '/');
        if (! $this->isFrontHome($path)) {
            return [];
        }

        $home = $this->abs(cms_front_url());
        $search = $this->abs(cms_front_url('search'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $home,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $search.'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
