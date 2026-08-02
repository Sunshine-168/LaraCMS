<?php

namespace App\Services\Cms\Theme;

use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use App\Services\Cms\SiteService;
use Illuminate\Support\Facades\View;

class ThemeResolver
{
    public function __construct(private readonly SiteService $siteService)
    {
    }

    public function theme(): string
    {
        try {
            $configured = (string) $this->siteService->get('index_theme', config('cms.theme', 'default'));
        } catch (\Throwable) {
            $configured = (string) config('cms.theme', 'default');
        }

        return $configured !== '' ? $configured : (string) config('cms.theme', 'default');
    }

    public function namespace(): string
    {
        return (string) config('cms.view_namespace', 'cms_theme');
    }

    public function registerViewNamespace(?string $theme = null): void
    {
        $theme = $theme ?: $this->theme();
        $base = rtrim((string) config('cms.theme_path', resource_path('views/themes')), DIRECTORY_SEPARATOR);
        $path = $base.DIRECTORY_SEPARATOR.$theme;
        $fallback = $base.DIRECTORY_SEPARATOR.config('cms.fallback_theme', 'default');

        $paths = array_values(array_unique(array_filter([
            is_dir($path) ? $path : null,
            is_dir($fallback) ? $fallback : null,
        ])));

        if ($paths !== []) {
            View::replaceNamespace($this->namespace(), $paths);
        }
    }

    public function homeView(): string
    {
        return $this->namespaced($this->defaultTemplate('index'));
    }

    public function categoryView(Category $category): string
    {
        return match ($category->type) {
            'list' => $this->namespaced($this->folderTemplate('list', $category->list_tpl, 'list')),
            'link' => $this->namespaced($this->folderTemplate('show', $category->show_tpl, 'page')),
            default => $this->namespaced($this->folderTemplate('category', $category->category_tpl, 'category')),
        };
    }

    public function showView(Archive $article, Category $category): string
    {
        $tpl = $article->show_tpl ?: $category->show_tpl;

        return $this->namespaced($this->folderTemplate('show', $tpl, 'show'));
    }

    public function namespaced(string $relative): string
    {
        return $this->namespace().'::'.str_replace(['/', '\\'], '.', $this->normalizeTemplate($relative));
    }

    public function resolveExisting(string $view): string
    {
        if (View::exists($view)) {
            return $view;
        }

        $fallbackTheme = (string) config('cms.fallback_theme', 'default');
        $relative = str_replace($this->namespace().'::', '', $view);
        $fallbackPath = rtrim((string) config('cms.theme_path'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$fallbackTheme;

        if (is_dir($fallbackPath)) {
            View::prependNamespace($this->namespace(), $fallbackPath);
        }

        if (View::exists($view)) {
            return $view;
        }

        $defaults = config('cms.default_templates', []);
        $parts = explode('.', $relative);
        $folder = $parts[0] ?? 'list';
        $key = match ($folder) {
            'index' => 'index',
            'category' => 'category',
            'show' => 'show',
            default => 'list',
        };

        $fallbackView = $this->namespaced($defaults[$key] ?? "{$folder}/{$folder}");

        return View::exists($fallbackView) ? $fallbackView : $view;
    }

    private function folderTemplate(string $folder, ?string $tpl, string $defaultKey): string
    {
        $normalized = $this->normalizeTemplate($tpl);

        if ($normalized === '') {
            return $this->defaultTemplate($defaultKey);
        }

        if (str_contains($normalized, '/')) {
            return $normalized;
        }

        return $folder.'/'.$normalized;
    }

    private function defaultTemplate(string $key): string
    {
        return (string) (config("cms.default_templates.{$key}") ?: "{$key}/{$key}");
    }

    private function normalizeTemplate(?string $tpl): string
    {
        if ($tpl === null || trim($tpl) === '') {
            return '';
        }

        $tpl = str_replace('\\', '/', trim($tpl));
        $tpl = preg_replace('/\.(html|blade\.php|php)$/i', '', $tpl) ?? $tpl;

        return trim($tpl, '/');
    }
}
