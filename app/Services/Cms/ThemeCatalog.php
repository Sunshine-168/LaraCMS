<?php

namespace App\Services\Cms;

use App\Cms\BundledContentModels;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Admin\SettingService;
use App\Support\CurrentSite;
use App\Support\Result;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ZipArchive;

/** 扫描 resources/views/themes，支持丢文件夹、复制或上传 zip */
class ThemeCatalog
{
    /** @return array<string, array{name:string,description:string}> */
    public function bundled(): array
    {
        return [
            'enterprise' => ['name' => '企业', 'description' => '公司官网'],
            'mobile' => ['name' => '移动', 'description' => '手机外观'],
            'download' => ['name' => '下载站', 'description' => '资料下载'],
            'nav' => ['name' => '导航', 'description' => '网址导航'],
            'source' => ['name' => '源码', 'description' => '源码展示'],
            'blog' => ['name' => '博客', 'description' => '文章博客'],
            'manga' => ['name' => '漫画', 'description' => '漫画连载'],
            'sports' => ['name' => '体育', 'description' => '赛事资讯'],
            'default' => ['name' => '默认', 'description' => '兜底模板'],
        ];
    }

    public function root(): string
    {
        return resource_path('views/themes');
    }

    public function currentSlug(): string
    {
        $site = CurrentSite::model();
        $slug = trim((string) ($site?->theme ?: Setting::getValue('theme', config('cms.theme', 'enterprise'))));

        return $slug !== '' ? $slug : 'enterprise';
    }

    public function exists(string $slug): bool
    {
        return $this->get($slug) !== null;
    }

    public function isBundled(string $slug): bool
    {
        return array_key_exists($slug, $this->bundled());
    }

    public function label(string $slug): string
    {
        return $this->get($slug)['name'] ?? $slug;
    }

    /** @return list<string> */
    public function slugs(): array
    {
        return array_column($this->all(), 'slug');
    }

    /**
     * @return list<array{slug:string,name:string,description:string,builtin:bool,complete:bool,current:bool,path:string,models:list<array{slug:string,name:string,ready:bool}>,models_ready:bool,preview_url:string,i18n:bool,responsive:bool}>
     */
    public function all(): array
    {
        $root = $this->root();
        $current = $this->currentSlug();
        $found = [];
        if (is_dir($root)) {
            foreach (File::directories($root) as $dir) {
                $slug = basename($dir);
                if (! $this->isValidSlug($slug)) {
                    continue;
                }
                $found[$slug] = $this->describe($slug, $dir, $current);
            }
        }
        foreach (array_keys($this->bundled()) as $slug) {
            if (! isset($found[$slug])) {
                $found[$slug] = $this->describe($slug, $root.DIRECTORY_SEPARATOR.$slug, $current);
            }
        }
        uasort($found, function (array $a, array $b) {
            if ($a['current'] !== $b['current']) {
                return $a['current'] ? -1 : 1;
            }
            if ($a['builtin'] !== $b['builtin']) {
                return $a['builtin'] ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return array_values($found);
    }

    /** @return array{slug:string,name:string,description:string,builtin:bool,complete:bool,current:bool,path:string,models:list<array{slug:string,name:string,ready:bool}>,models_ready:bool,preview_url:string,i18n:bool,responsive:bool}|null */
    public function get(string $slug): ?array
    {
        if (! $this->isValidSlug($slug)) {
            return null;
        }
        $dir = $this->root().DIRECTORY_SEPARATOR.$slug;
        if (! is_dir($dir) && ! $this->isBundled($slug)) {
            return null;
        }

        return $this->describe($slug, $dir, $this->currentSlug());
    }

    /** @return array<string, mixed> */
    public function manifest(string $slug): array
    {
        if (! $this->isValidSlug($slug)) {
            return [];
        }

        return $this->readManifest($this->root().DIRECTORY_SEPARATOR.$slug);
    }

    /** @return array<string, mixed> */
    public function setup(string $slug): array
    {
        if (! $this->isValidSlug($slug)) {
            return [];
        }
        try {
            $raw = Setting::getValue('theme_setup.'.$slug, '');
        } catch (\Throwable) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array{i18n?:bool,responsive?:bool,models?:list<string>}  $data
     */
    public function saveSetup(string $slug, array $data): void
    {
        if (! $this->isValidSlug($slug)) {
            return;
        }
        $models = [];
        foreach ((array) ($data['models'] ?? []) as $model) {
            $model = strtolower(trim((string) $model));
            if ($this->isValidModelSlug($model)) {
                $models[] = $model;
            }
        }
        Setting::setValue('theme_setup.'.$slug, json_encode([
            'i18n' => ! empty($data['i18n']),
            'responsive' => ! empty($data['responsive']),
            'models' => array_values(array_unique($models)),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, string> */
    public function choosableModels(string $slug): array
    {
        $out = [];
        foreach (BundledContentModels::definitions() as $modelSlug => $def) {
            $out[$modelSlug] = (string) ($def['name'] ?? $modelSlug);
        }
        foreach (ContentModel::query()->orderBy('sort_order')->orderBy('id')->get() as $model) {
            $out[$model->slug] = (string) $model->name;
        }
        $item = $this->get($slug);
        foreach ((array) ($item['models'] ?? []) as $row) {
            $modelSlug = (string) ($row['slug'] ?? '');
            if ($modelSlug !== '') {
                $out[$modelSlug] = (string) ($row['name'] ?? $modelSlug);
            }
        }
        ksort($out);

        return $out;
    }

    public function supportsI18n(?string $slug = null): bool
    {
        $slug = trim((string) ($slug ?: config('cms.theme', $this->currentSlug())));
        if (! $this->isValidSlug($slug)) {
            return true;
        }
        $setup = $this->setup($slug);
        if (array_key_exists('i18n', $setup)) {
            return (bool) $setup['i18n'];
        }

        return $this->manifestFlag($this->manifest($slug), 'i18n');
    }

    public function apply(string $slug): array
    {
        $item = $this->get($slug);
        if (! $item || ! $item['complete']) {
            return Result::fail('没有这套主题，或还缺首页模板');
        }
        $pack = app(ThemePackInstaller::class)->ensure($slug);
        app(SettingService::class)->save(['theme' => $slug]);
        Artisan::call('view:clear');
        $site = CurrentSite::model();
        if ($site instanceof Site && $site->theme) {
            $site->theme = $slug;
            $site->save();
        }

        $msg = '前台已换成「'.$this->label($slug).'」';
        $extra = trim((string) ($pack['msg'] ?? ''));
        if ($extra !== '') {
            $msg .= '。'.$extra;
        }

        return Result::ok(['slug' => $slug], $msg);
    }

    public function copy(string $from, string $slug, string $name, string $description = ''): array
    {
        $from = trim($from);
        $slug = strtolower(trim($slug));
        $name = trim($name);
        if (! $this->isValidSlug($from) || ! is_dir($this->root().DIRECTORY_SEPARATOR.$from)) {
            return Result::fail('请选择要复制的主题');
        }
        if (! $this->isValidSlug($slug)) {
            return Result::fail('标识用小写英文开头，可含数字和短横线，如 brand');
        }
        if (is_dir($this->root().DIRECTORY_SEPARATOR.$slug)) {
            return Result::fail('这个标识已经有主题了，换一个');
        }
        if ($name === '') {
            return Result::fail('请填写显示名称');
        }

        $source = $this->root().DIRECTORY_SEPARATOR.$from;
        $dest = $this->root().DIRECTORY_SEPARATOR.$slug;
        File::copyDirectory($source, $dest);
        foreach (File::allFiles($dest) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $text = File::get($file->getPathname());
            $text = str_replace('themes.'.$from.'.', 'themes.'.$slug.'.', $text);
            File::put($file->getPathname(), $text);
        }
        $this->writeManifest($slug, $name, $description, $from);

        return Result::ok(['slug' => $slug], '已复制成「'.$name.'」。改 Blade 就会只影响这一套。');
    }

    public function installZip(string $zipPath, string $slug = '', string $name = '', string $hint = ''): array
    {
        if (! class_exists(ZipArchive::class)) {
            return Result::fail('这台服务器还不能解压 zip');
        }
        if ($zipPath === '' || ! is_file($zipPath)) {
            return Result::fail('请选择 zip 文件');
        }
        if (filesize($zipPath) > 8 * 1024 * 1024) {
            return Result::fail('zip 不要超过 8MB');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return Result::fail('打不开这个 zip');
        }

        $tmp = storage_path('app/theme-upload-'.uniqid('', true));
        try {
            $parsed = $this->inspectThemeZip($zip);
            if (! $parsed['ok']) {
                return Result::fail($parsed['msg']);
            }

            $slug = strtolower(trim($slug));
            if ($slug === '') {
                $slug = $parsed['slug'];
            }
            if ($slug === '' && $this->isValidSlug(strtolower(trim($hint)))) {
                $slug = strtolower(trim($hint));
            }
            if (! $this->isValidSlug($slug)) {
                return Result::fail('标识用小写英文开头，可含数字和短横线，如 brand');
            }
            if ($this->isBundled($slug) || is_dir($this->root().DIRECTORY_SEPARATOR.$slug)) {
                return Result::fail('这个标识已经有主题了，换一个');
            }

            File::makeDirectory($tmp, 0755, true);
            $written = $this->extractThemeZip($zip, $tmp, $parsed['prefix']);
            if ($written === 0) {
                return Result::fail('zip 里没有可用的主题文件');
            }

            $name = trim($name) ?: ($this->nameFromManifest($tmp) ?: $slug);
            $dest = $this->root().DIRECTORY_SEPARATOR.$slug;
            File::copyDirectory($tmp, $dest);
            $this->writeManifest($slug, $name, $this->descriptionFromManifest($dest), 'upload');

            $item = $this->get($slug);
            if (! $item || ! $item['complete']) {
                return Result::ok(['slug' => $slug], '已放上「'.$name.'」，但还缺首页模板，补齐 index/index.blade.php 才能用于本站');
            }

            return Result::ok(['slug' => $slug], '已上传「'.$name.'」。点用于本站即可换成这套。');
        } finally {
            $zip->close();
            if (is_dir($tmp)) {
                File::deleteDirectory($tmp);
            }
        }
    }

    public function remove(string $slug): array
    {
        $slug = trim($slug);
        if ($this->isBundled($slug)) {
            return Result::fail('自带主题不能删');
        }
        if ($this->currentSlug() === $slug) {
            return Result::fail('正在用的主题不能删，先换一套');
        }
        $dir = $this->root().DIRECTORY_SEPARATOR.$slug;
        if (! is_dir($dir)) {
            return Result::fail('没有这套主题');
        }
        File::deleteDirectory($dir);

        return Result::ok(null, '已去掉这套主题');
    }

    public function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $slug);
    }

    /**
     * @return array{slug:string,name:string,description:string,builtin:bool,complete:bool,current:bool,path:string,models:list<array{slug:string,name:string,ready:bool}>,models_ready:bool,preview_url:string}
     */
    protected function describe(string $slug, string $dir, string $current): array
    {
        $meta = $this->bundled()[$slug] ?? ['name' => $slug, 'description' => ''];
        $json = $this->readManifest($dir);
        if (! empty($json['name'])) {
            $meta['name'] = (string) $json['name'];
        }
        if (array_key_exists('description', $json)) {
            $meta['description'] = (string) $json['description'];
        }
        $models = $this->packModelSummaries($dir, $json, $slug);
        $setup = $this->setup($slug);

        return [
            'slug' => $slug,
            'name' => $meta['name'],
            'description' => $meta['description'],
            'builtin' => $this->isBundled($slug),
            'complete' => is_file($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php'),
            'current' => $current === $slug,
            'path' => 'resources/views/themes/'.$slug,
            'models' => $models,
            'models_ready' => $models === [] || ! in_array(false, array_column($models, 'ready'), true),
            'preview_url' => route('admin.themes.preview', $slug),
            'i18n' => array_key_exists('i18n', $setup)
                ? (bool) $setup['i18n']
                : $this->manifestFlag($json, 'i18n'),
            'responsive' => array_key_exists('responsive', $setup)
                ? (bool) $setup['responsive']
                : $this->manifestFlag($json, 'responsive'),
        ];
    }

    /** @param  array<string, mixed>  $json */
    protected function manifestFlag(array $json, string $key): bool
    {
        if (! array_key_exists($key, $json)) {
            return true;
        }

        return filter_var($json[$key], FILTER_VALIDATE_BOOLEAN);
    }

    public function previewPath(string $slug): ?string
    {
        if (! $this->isValidSlug($slug)) {
            return null;
        }
        $dir = $this->root().DIRECTORY_SEPARATOR.$slug;
        $json = $this->readManifest($dir);
        if (! empty($json['preview']) && is_string($json['preview'])) {
            $rel = str_replace('\\', '/', $json['preview']);
            if (! str_contains($rel, '..') && ! str_starts_with($rel, '/') && ! preg_match('#^[a-zA-Z]:#', $rel)) {
                $file = $dir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
                if (is_file($file) && $this->isPreviewImage($file)) {
                    return $file;
                }
            }
        }
        foreach (['preview.png', 'preview.jpg', 'preview.jpeg', 'preview.webp', 'preview.svg', 'preview.gif', 'screenshot.png'] as $name) {
            $file = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($file) && $this->isPreviewImage($file)) {
                return $file;
            }
        }

        return null;
    }

    public function previewMime(?string $path): ?string
    {
        if (! $path || ! is_file($path)) {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };
    }

    public function previewSvg(string $slug): string
    {
        $json = $this->readManifest($this->root().DIRECTORY_SEPARATOR.$slug);
        $name = trim((string) ($json['name'] ?? ''));
        if ($name === '') {
            $name = $this->bundled()[$slug]['name'] ?? $slug;
        }
        $name = e($name);
        $p = $this->previewPalette($slug);
        $bg = $p['bg'];
        $bar = $p['bar'];
        $accent = $p['accent'];
        $hero = $p['hero'];
        $card = $p['card'];
        $ink = $p['ink'];

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 300" role="img" aria-label="{$name}">
  <rect width="480" height="300" rx="20" fill="{$bg}"/>
  <rect x="22" y="18" width="436" height="264" rx="14" fill="{$bar}"/>
  <circle cx="46" cy="40" r="5" fill="#f87171"/>
  <circle cx="64" cy="40" r="5" fill="#fbbf24"/>
  <circle cx="82" cy="40" r="5" fill="#34d399"/>
  <rect x="102" y="32" width="236" height="16" rx="8" fill="{$bg}"/>
  <rect x="40" y="62" width="400" height="78" rx="10" fill="{$hero}"/>
  <text x="56" y="108" fill="#ffffff" font-size="22" font-family="Segoe UI,Noto Sans SC,sans-serif" font-weight="700">{$name}</text>
  <rect x="40" y="156" width="190" height="96" rx="10" fill="{$card}"/>
  <rect x="56" y="176" width="110" height="10" rx="5" fill="{$accent}" opacity=".85"/>
  <rect x="56" y="196" width="150" height="8" rx="4" fill="{$ink}" opacity=".18"/>
  <rect x="56" y="214" width="128" height="8" rx="4" fill="{$ink}" opacity=".12"/>
  <rect x="250" y="156" width="190" height="96" rx="10" fill="{$card}"/>
  <rect x="266" y="176" width="90" height="10" rx="5" fill="{$accent}" opacity=".7"/>
  <rect x="266" y="196" width="140" height="8" rx="4" fill="{$ink}" opacity=".18"/>
  <rect x="266" y="214" width="118" height="8" rx="4" fill="{$ink}" opacity=".12"/>
</svg>
SVG;
    }

    /** @return array{bg:string,bar:string,accent:string,hero:string,card:string,ink:string} */
    protected function previewPalette(string $slug): array
    {
        $base = match ($slug) {
            'enterprise' => ['bg' => '#e8eef7', 'bar' => '#ffffff', 'accent' => '#1b4f72', 'hero' => '#1b4f72', 'card' => '#f4f7fb', 'ink' => '#0f2744'],
            'mobile' => ['bg' => '#e2e8f0', 'bar' => '#ffffff', 'accent' => '#0ea5e9', 'hero' => '#0ea5e9', 'card' => '#f8fafc', 'ink' => '#111827'],
            'download' => ['bg' => '#020617', 'bar' => '#0b1220', 'accent' => '#0284c7', 'hero' => '#1e3a5f', 'card' => '#111827', 'ink' => '#e5eefc'],
            'nav' => ['bg' => '#f5efe6', 'bar' => '#fffaf5', 'accent' => '#0f766e', 'hero' => '#0f766e', 'card' => '#ffffff', 'ink' => '#1c1917'],
            'source' => ['bg' => '#090909', 'bar' => '#111111', 'accent' => '#65a30d', 'hero' => '#1a2e0a', 'card' => '#171717', 'ink' => '#f5f5f5'],
            'blog' => ['bg' => '#f3eee8', 'bar' => '#fffdf9', 'accent' => '#9a3412', 'hero' => '#9a3412', 'card' => '#ffffff', 'ink' => '#1c1917'],
            'manga' => ['bg' => '#0a0a0a', 'bar' => '#121212', 'accent' => '#f59e0b', 'hero' => '#1f1608', 'card' => '#1a1a1a', 'ink' => '#f5f5f5'],
            'sports' => ['bg' => '#02110c', 'bar' => '#04110d', 'accent' => '#10b981', 'hero' => '#065f46', 'card' => '#06281f', 'ink' => '#ecfdf5'],
            'default' => ['bg' => '#eef2ff', 'bar' => '#ffffff', 'accent' => '#1d3557', 'hero' => '#1d3557', 'card' => '#f8fafc', 'ink' => '#14213d'],
            default => ['bg' => '#eef2f7', 'bar' => '#ffffff', 'accent' => '#2563eb', 'hero' => '#3b82f6', 'card' => '#f8fafc', 'ink' => '#111827'],
        };
        $json = $this->readManifest($this->root().DIRECTORY_SEPARATOR.$slug);
        if (is_array($json['preview'] ?? null)) {
            foreach (['bg', 'bar', 'accent', 'hero', 'card', 'ink'] as $key) {
                $value = trim((string) ($json['preview'][$key] ?? ''));
                if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

    protected function isPreviewImage(string $path): bool
    {
        return $this->previewMime($path) !== null;
    }

    /**
     * @return list<array{version?:int,model:array<string,mixed>,fields?:list<array<string,mixed>>}>
     */
    public function packModelPayloads(string $slug): array
    {
        $dir = $this->root().DIRECTORY_SEPARATOR.$slug;
        $json = $this->readManifest($dir);
        $out = [];
        foreach ($this->packModelSlugs($dir, $json, $slug) as $modelSlug) {
            $payload = $this->resolveModelPayload($dir, $json, $modelSlug);
            if ($payload !== null) {
                $out[] = $payload;
            }
        }

        return $out;
    }

    /**
     * @return list<array{slug:string,title:string,model:string,type?:string,menu?:bool,list_tpl?:?string,show_tpl?:?string}>
     */
    public function packCategories(string $slug): array
    {
        $json = $this->readManifest($this->root().DIRECTORY_SEPARATOR.$slug);
        $cats = $json['categories'] ?? [];
        if (! is_array($cats)) {
            return [];
        }
        $out = [];
        foreach ($cats as $row) {
            if (is_array($row) && ! empty($row['slug']) && $this->categoryTitleFilled($row['title'] ?? null) && ! empty($row['model'])) {
                $out[] = $row;
            }
        }

        return $out;
    }

    protected function categoryTitleFilled(mixed $title): bool
    {
        if (is_array($title)) {
            foreach ($title as $text) {
                if (trim((string) $text) !== '') {
                    return true;
                }
            }

            return false;
        }

        return trim((string) $title) !== '';
    }

    protected function writeManifest(string $slug, string $name, string $description, string $from): void
    {
        $dir = $this->root().DIRECTORY_SEPARATOR.$slug;
        $existing = $this->readManifest($dir);
        $payload = [
            'name' => $name,
            'description' => $description,
            'from' => $from,
        ];
        foreach (['models', 'categories', 'preview', 'i18n', 'responsive', 'config'] as $key) {
            if (array_key_exists($key, $existing)) {
                $payload[$key] = $existing[$key];
            }
        }
        File::put(
            $dir.DIRECTORY_SEPARATOR.'theme.json',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
        );
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<array{slug:string,name:string,ready:bool}>
     */
    protected function packModelSummaries(string $dir, array $json, string $slug): array
    {
        $out = [];
        foreach ($this->packModelSlugs($dir, $json, $slug) as $modelSlug) {
            $payload = $this->resolveModelPayload($dir, $json, $modelSlug);
            $out[] = [
                'slug' => $modelSlug,
                'name' => (string) ((is_array($payload) ? ($payload['model']['name'] ?? null) : null) ?: $modelSlug),
                'ready' => ContentModel::query()->where('slug', $modelSlug)->exists(),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return list<string>
     */
    protected function packModelSlugs(string $dir, array $json, string $slug): array
    {
        $slugs = [];
        $setup = $this->setup($slug);
        $listed = array_key_exists('models', $setup) ? $setup['models'] : ($json['models'] ?? []);
        if (is_array($listed)) {
            foreach ($listed as $row) {
                if (is_string($row) && $this->isValidModelSlug($row)) {
                    $slugs[] = $row;
                } elseif (is_array($row) && ! empty($row['slug']) && $this->isValidModelSlug((string) $row['slug'])) {
                    $slugs[] = (string) $row['slug'];
                }
            }
        }
        $packDir = $dir.DIRECTORY_SEPARATOR.'models';
        if (is_dir($packDir)) {
            foreach (File::files($packDir) as $file) {
                if (strtolower($file->getExtension()) !== 'json') {
                    continue;
                }
                $payload = json_decode((string) File::get($file->getPathname()), true);
                $modelSlug = is_array($payload) ? (string) ($payload['model']['slug'] ?? '') : '';
                if ($this->isValidModelSlug($modelSlug)) {
                    $slugs[] = $modelSlug;
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{version?:int,model:array<string,mixed>,fields?:list<array<string,mixed>>}|null
     */
    protected function resolveModelPayload(string $dir, array $json, string $modelSlug): ?array
    {
        $file = $dir.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$modelSlug.'.json';
        if (is_file($file)) {
            $payload = json_decode((string) File::get($file), true);
            if (is_array($payload) && ! empty($payload['model']['slug'])) {
                return $payload;
            }
        }
        $listed = $json['models'] ?? [];
        if (is_array($listed)) {
            foreach ($listed as $row) {
                if (is_array($row) && ($row['slug'] ?? '') === $modelSlug && ! empty($row['name'])) {
                    $row['slug'] = $modelSlug;
                    $fields = is_array($row['fields'] ?? null) ? $row['fields'] : [];
                    unset($row['fields']);

                    return ['version' => 1, 'model' => $row, 'fields' => $fields];
                }
            }
        }

        return BundledContentModels::exportPayload($modelSlug);
    }

    protected function isValidModelSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]{0,31}$/', $slug);
    }

    /**
     * @return array{ok:bool,msg:string,prefix:string,slug:string}
     */
    protected function inspectThemeZip(ZipArchive $zip): array
    {
        $count = $zip->numFiles;
        if ($count < 1) {
            return ['ok' => false, 'msg' => 'zip 是空的', 'prefix' => '', 'slug' => ''];
        }
        if ($count > 400) {
            return ['ok' => false, 'msg' => 'zip 里文件太多', 'prefix' => '', 'slug' => ''];
        }

        $entries = [];
        $bytes = 0;
        for ($i = 0; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            if (! is_array($stat)) {
                continue;
            }
            $entry = $this->normalizeZipPath((string) ($stat['name'] ?? ''));
            if ($entry === null) {
                return ['ok' => false, 'msg' => 'zip 路径不合法', 'prefix' => '', 'slug' => ''];
            }
            if ($entry === '' || str_ends_with($entry, '/')) {
                continue;
            }
            if ($this->isJunkZipPath($entry)) {
                continue;
            }
            $bytes += (int) ($stat['size'] ?? 0);
            if ($bytes > 20 * 1024 * 1024) {
                return ['ok' => false, 'msg' => '解压后太大', 'prefix' => '', 'slug' => ''];
            }
            $entries[] = $entry;
        }
        if ($entries === []) {
            return ['ok' => false, 'msg' => 'zip 里没有可用的主题文件', 'prefix' => '', 'slug' => ''];
        }

        $prefix = $this->themeZipPrefix($entries);
        $home = $prefix.'index/index.blade.php';
        if (! in_array($home, $entries, true)) {
            return ['ok' => false, 'msg' => 'zip 里要有 index/index.blade.php，可以放在根目录或一层文件夹里', 'prefix' => '', 'slug' => ''];
        }

        $slug = '';
        if ($prefix !== '' && $this->isValidSlug(rtrim($prefix, '/'))) {
            $slug = rtrim($prefix, '/');
        }

        return ['ok' => true, 'msg' => '', 'prefix' => $prefix, 'slug' => $slug];
    }

    /** @param list<string> $entries */
    protected function themeZipPrefix(array $entries): string
    {
        if (in_array('index/index.blade.php', $entries, true)) {
            return '';
        }

        $homes = [];
        foreach ($entries as $entry) {
            if (preg_match('#^([a-z][a-z0-9_-]{0,31})/index/index\.blade\.php$#', $entry, $m)) {
                $homes[$m[1]] = true;
            }
        }
        if (count($homes) === 1) {
            return array_key_first($homes).'/';
        }

        $tops = [];
        foreach ($entries as $entry) {
            if (str_contains($entry, '/')) {
                $tops[strstr($entry, '/', true)] = true;
            } else {
                $tops[''] = true;
            }
        }
        if (count($tops) === 1 && ! isset($tops[''])) {
            return array_key_first($tops).'/';
        }

        return '';
    }

    protected function extractThemeZip(ZipArchive $zip, string $tmp, string $prefix): int
    {
        $written = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $raw = (string) $zip->getNameIndex($i);
            $entry = $this->normalizeZipPath($raw);
            if ($entry === null || $entry === '' || str_ends_with($entry, '/')) {
                continue;
            }
            if ($this->isJunkZipPath($entry)) {
                continue;
            }
            $rel = $prefix === '' ? $entry : (str_starts_with($entry, $prefix) ? substr($entry, strlen($prefix)) : '');
            $rel = ltrim(str_replace('\\', '/', $rel), '/');
            if ($rel === '' || ! $this->isAllowedThemeFile($rel)) {
                continue;
            }
            $body = $zip->getFromIndex($i);
            if (! is_string($body)) {
                continue;
            }
            $target = $tmp.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $body);
            $written++;
        }

        return $written;
    }

    protected function isAllowedThemeFile(string $rel): bool
    {
        $rel = str_replace('\\', '/', $rel);
        if ($rel === 'theme.json' || str_ends_with(strtolower($rel), '.blade.php')) {
            return true;
        }
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));

        return in_array($ext, [
            'css', 'js', 'map', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
            'woff', 'woff2', 'ttf', 'eot', 'md', 'txt', 'html', 'htm',
        ], true);
    }

    protected function isJunkZipPath(string $entry): bool
    {
        $entry = str_replace('\\', '/', $entry);

        return str_starts_with($entry, '__MACOSX/')
            || str_contains($entry, '/__MACOSX/')
            || str_ends_with($entry, '/.DS_Store')
            || $entry === '.DS_Store'
            || str_ends_with(strtolower($entry), '/thumbs.db');
    }

    protected function normalizeZipPath(string $name): ?string
    {
        $name = str_replace('\\', '/', $name);
        $name = ltrim($name, '/');
        if ($name === '') {
            return '';
        }
        if (str_contains($name, "\0") || str_contains($name, '..') || preg_match('#^[a-zA-Z]:#', $name)) {
            return null;
        }

        return $name;
    }

    protected function nameFromManifest(string $dir): string
    {
        $json = $this->readManifest($dir);

        return trim((string) ($json['name'] ?? ''));
    }

    protected function descriptionFromManifest(string $dir): string
    {
        $json = $this->readManifest($dir);

        return (string) ($json['description'] ?? '');
    }

    /** @return array<string, mixed> */
    protected function readManifest(string $dir): array
    {
        $file = $dir.DIRECTORY_SEPARATOR.'theme.json';
        if (! is_file($file)) {
            return [];
        }
        $json = json_decode((string) File::get($file), true);

        return is_array($json) ? $json : [];
    }
}
