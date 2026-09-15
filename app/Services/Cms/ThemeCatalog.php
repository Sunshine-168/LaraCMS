<?php

namespace App\Services\Cms;

use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Admin\SettingService;
use App\Support\CurrentSite;
use App\Support\Result;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/** 扫描 resources/views/themes，支持丢文件夹或从现有主题复制 */
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
     * @return list<array{slug:string,name:string,description:string,builtin:bool,complete:bool,current:bool,path:string}>
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

    /** @return array{slug:string,name:string,description:string,builtin:bool,complete:bool,current:bool,path:string}|null */
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

    public function apply(string $slug): array
    {
        $item = $this->get($slug);
        if (! $item || ! $item['complete']) {
            return Result::fail('没有这套主题，或还缺首页模板');
        }
        app(SettingService::class)->save(['theme' => $slug]);
        Artisan::call('view:clear');
        $site = CurrentSite::model();
        if ($site instanceof Site && $site->theme) {
            $site->theme = $slug;
            $site->save();
        }

        return Result::ok(['slug' => $slug], '前台已换成「'.$this->label($slug).'」');
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
     * @return array{slug:string,name:string,description:string,builtin:bool,complete:bool,current:bool,path:string}
     */
    protected function describe(string $slug, string $dir, string $current): array
    {
        $meta = $this->bundled()[$slug] ?? ['name' => $slug, 'description' => ''];
        $file = $dir.DIRECTORY_SEPARATOR.'theme.json';
        if (is_file($file)) {
            $json = json_decode((string) File::get($file), true);
            if (is_array($json)) {
                if (! empty($json['name'])) {
                    $meta['name'] = (string) $json['name'];
                }
                if (array_key_exists('description', $json)) {
                    $meta['description'] = (string) $json['description'];
                }
            }
        }

        return [
            'slug' => $slug,
            'name' => $meta['name'],
            'description' => $meta['description'],
            'builtin' => $this->isBundled($slug),
            'complete' => is_file($dir.DIRECTORY_SEPARATOR.'index'.DIRECTORY_SEPARATOR.'index.blade.php'),
            'current' => $current === $slug,
            'path' => 'resources/views/themes/'.$slug,
        ];
    }

    protected function writeManifest(string $slug, string $name, string $description, string $from): void
    {
        $payload = [
            'name' => $name,
            'description' => $description,
            'from' => $from,
        ];
        File::put(
            $this->root().DIRECTORY_SEPARATOR.$slug.DIRECTORY_SEPARATOR.'theme.json',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
        );
    }
}
