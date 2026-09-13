<?php

namespace App\Services\Cms\Stats;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Support\Locale;

class PageTitleResolver
{
    /** @var array<string, string>|null */
    protected ?array $map = null;

    /**
     * @param  iterable<int, string>  $paths
     * @return array<string, string>
     */
    public function forPaths(iterable $paths): array
    {
        $out = [];
        foreach ($paths as $path) {
            $path = (string) $path;
            $out[$path] = $this->title($path);
        }

        return $out;
    }

    public function title(string $path): string
    {
        $norm = $this->normalize($path);
        $map = $this->map();
        if (isset($map[$norm])) {
            return $map[$norm];
        }

        $stripped = $this->stripLocale($norm);
        if (isset($map[$stripped])) {
            return $map[$stripped];
        }

        return $this->fallback($stripped !== '' ? $stripped : $norm);
    }

    /** @return array<string, string> */
    protected function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $map = [
            '/' => '首页',
            '/search' => '搜索',
            '/member' => '会员中心',
            '/member/login' => '会员登录',
            '/member/register' => '会员注册',
            '/member/forgot' => '找回密码',
            '/member/profile' => '会员资料',
            '/member/entries' => '会员投稿',
            '/rss.xml' => 'RSS',
            '/sitemap.xml' => '站点地图',
        ];

        foreach (Category::query()->with('modelDef')->get() as $category) {
            $title = (string) ($category->t('title') ?: $category->t('slug') ?: '栏目');
            $this->remember($map, $category->frontUrl(), $title);
            $this->remember($map, '/c/'.$category->id, $title);
        }

        foreach (Entry::query()->with(['category', 'modelDef'])->get() as $entry) {
            $title = (string) ($entry->t('title') ?: '文档');
            $this->remember($map, $entry->url, $title);
            $this->remember($map, '/e/'.$entry->id, $title);
        }

        return $this->map = $map;
    }

    /** @param  array<string, string>  $map */
    protected function remember(array &$map, string $url, string $title): void
    {
        if ($url === '' || $url === '#') {
            return;
        }
        $path = $this->normalize($url);
        $map[$path] = $title;
        $stripped = $this->stripLocale($path);
        if ($stripped !== $path) {
            $map[$stripped] = $title;
        }
    }

    protected function fallback(string $path): string
    {
        if (preg_match('#^/tag/([^/]+)$#', $path, $m)) {
            return '标签 '.$m[1];
        }
        if (preg_match('#^/form/([^/]+)$#', $path, $m)) {
            return '表单 '.$m[1];
        }
        if (preg_match('#^/download/#', $path)) {
            return '下载';
        }
        if (preg_match('#^/go/#', $path)) {
            return '外链跳转';
        }
        if (preg_match('#^/member/#', $path)) {
            return '会员中心';
        }

        return $path;
    }

    public function normalize(string $path): string
    {
        $parsed = parse_url($path, PHP_URL_PATH);
        $path = is_string($parsed) && $parsed !== '' ? $parsed : $path;
        $path = '/'.ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    protected function stripLocale(string $path): string
    {
        foreach (Locale::codes() as $code) {
            $prefix = '/'.strtolower(Locale::toPublic($code));
            if ($path === $prefix) {
                return '/';
            }
            if (str_starts_with(strtolower($path), $prefix.'/')) {
                $rest = substr($path, strlen($prefix));

                return $rest === '' ? '/' : $rest;
            }
        }

        return $path;
    }
}
