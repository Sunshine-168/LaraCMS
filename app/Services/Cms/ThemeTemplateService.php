<?php

namespace App\Services\Cms;

use App\Support\Result;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ThemeTemplateService
{
    public function __construct(
        private readonly ThemeCatalog $themes,
        private readonly HtmlCacheService $htmlCache,
    ) {}

    /**
     * @return list<array{key:string,label:string,files:list<array{path:string,name:string,label:string}>}>
     */
    public function groups(string $slug): array
    {
        $root = $this->themeDir($slug);
        if (! is_dir($root)) {
            return [];
        }

        $grouped = [];
        foreach (File::allFiles($root) as $file) {
            $rel = str_replace('\\', '/', $file->getRelativePathname());
            if (! str_ends_with(strtolower($rel), '.blade.php')) {
                continue;
            }
            $meta = $this->fileMeta($rel);
            $grouped[$meta['group']]['label'] = $meta['group_label'];
            $grouped[$meta['group']]['files'][] = [
                'path' => $rel,
                'name' => basename($rel),
                'label' => $meta['label'],
            ];
        }

        $order = [
            'layout' => '首页与布局',
            'list' => '列表',
            'show' => '详情',
            'member' => '会员',
            'partials' => '公共块',
            'other' => '其他',
        ];
        $out = [];
        foreach ($order as $key => $label) {
            if (empty($grouped[$key]['files'])) {
                continue;
            }
            $files = $grouped[$key]['files'];
            usort($files, fn ($a, $b) => strcmp($a['label'], $b['label']));
            $out[] = [
                'key' => $key,
                'label' => $grouped[$key]['label'] ?? $label,
                'files' => $files,
            ];
        }

        return $out;
    }

    public function read(string $slug, string $rel): array
    {
        $path = $this->safePath($slug, $rel);
        if ($path === null) {
            return Result::fail('非法路径');
        }
        if (! is_file($path)) {
            return Result::fail('文件不存在');
        }

        return Result::ok([
            'path' => str_replace('\\', '/', $rel),
            'content' => (string) File::get($path),
            'backup_at' => $this->latestBackupTime($slug, $rel),
            'label' => $this->fileMeta($rel)['label'],
            'writable' => $this->writable($slug),
        ]);
    }

    public function save(string $slug, string $rel, string $content): array
    {
        $locked = $this->guardWrite($slug, $rel);
        if ($locked !== null) {
            return $locked;
        }
        $path = $this->safePath($slug, $rel);
        if ($path === null || ! is_file($path)) {
            return Result::fail('文件不存在');
        }
        $this->backup($slug, $rel);
        File::put($path, $content);
        $this->clearCaches($slug);

        return Result::ok(['backup_at' => $this->latestBackupTime($slug, $rel)], '已保存，并做了备份');
    }

    public function backup(string $slug, string $rel): array
    {
        $locked = $this->guardWrite($slug, $rel);
        if ($locked !== null) {
            return $locked;
        }
        $path = $this->safePath($slug, $rel);
        if ($path === null || ! is_file($path)) {
            return Result::fail('文件不存在');
        }
        $stamp = date('YmdHis');
        $dest = $this->backupDir($slug).DIRECTORY_SEPARATOR.$stamp.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
        File::ensureDirectoryExists(dirname($dest));
        File::copy($path, $dest);

        return Result::ok(['backup_at' => $this->latestBackupTime($slug, $rel)], '已备份');
    }

    public function rollback(string $slug, string $rel): array
    {
        $locked = $this->guardWrite($slug, $rel);
        if ($locked !== null) {
            return $locked;
        }
        $path = $this->safePath($slug, $rel);
        if ($path === null) {
            return Result::fail('非法路径');
        }
        $root = $this->backupDir($slug);
        if (! is_dir($root)) {
            return Result::fail('没有备份');
        }
        $dirs = File::directories($root);
        rsort($dirs);
        $latest = null;
        foreach ($dirs as $dir) {
            $cand = $dir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($cand)) {
                $latest = $cand;
                break;
            }
        }
        if ($latest === null) {
            return Result::fail('没有该文件的备份');
        }
        File::copy($latest, $path);
        $this->clearCaches($slug);

        return Result::ok(['backup_at' => $this->latestBackupTime($slug, $rel)], '已回到上次备份');
    }

    public function writable(string $slug): bool
    {
        return $this->themes->isValidSlug($slug) && ! $this->themes->isBundled($slug) && is_dir($this->themeDir($slug));
    }

    /** @return array{ok:false,msg:string,data:mixed}|null */
    protected function guardWrite(string $slug, string $rel): ?array
    {
        if (! str_ends_with(strtolower(str_replace('\\', '/', $rel)), '.blade.php')) {
            return Result::fail('只能编辑 blade 模板');
        }
        if (! $this->writable($slug)) {
            return Result::fail('自带主题请先复制一套再改');
        }

        return null;
    }

    /** @return array{label:string,group:string,group_label:string} */
    protected function fileMeta(string $rel): array
    {
        $rel = str_replace('\\', '/', $rel);
        $map = [
            'index/index.blade.php' => ['首页', 'layout', '首页与布局'],
            'partials/layout.blade.php' => ['整站头尾', 'layout', '首页与布局'],
            'layout.blade.php' => ['整站头尾', 'layout', '首页与布局'],
            'partials/paginate.blade.php' => ['分页', 'partials', '公共块'],
            'list/list.blade.php' => ['栏目列表', 'list', '列表'],
            'list/list_product.blade.php' => ['产品列表', 'list', '列表'],
            'show/show.blade.php' => ['文档详情', 'show', '详情'],
            'show/show_product.blade.php' => ['产品详情', 'show', '详情'],
            'show/chapter.blade.php' => ['章节', 'show', '详情'],
            'category/category.blade.php' => ['栏目封面', 'list', '列表'],
            'search.blade.php' => ['搜索', 'other', '其他'],
            'tag.blade.php' => ['标签页', 'other', '其他'],
            'sitemap.blade.php' => ['站点地图 XML', 'other', '其他'],
            'sitemap-page.blade.php' => ['站点地图网页', 'other', '其他'],
            'member/login.blade.php' => ['登录', 'member', '会员'],
            'member/register.blade.php' => ['注册', 'member', '会员'],
            'member/profile.blade.php' => ['会员资料', 'member', '会员'],
            'member/forgot.blade.php' => ['找回密码', 'member', '会员'],
            'member/reset.blade.php' => ['重置密码', 'member', '会员'],
            'member/entries.blade.php' => ['我的投稿', 'member', '会员'],
            'member/entry-form.blade.php' => ['写稿', 'member', '会员'],
        ];
        if (isset($map[$rel])) {
            return ['label' => $map[$rel][0], 'group' => $map[$rel][1], 'group_label' => $map[$rel][2]];
        }
        $base = basename($rel, '.blade.php');
        if (str_starts_with($rel, 'member/')) {
            return ['label' => $base, 'group' => 'member', 'group_label' => '会员'];
        }
        if (str_starts_with($rel, 'partials/')) {
            return ['label' => $base, 'group' => 'partials', 'group_label' => '公共块'];
        }
        if (str_starts_with($rel, 'index/')) {
            return ['label' => $base, 'group' => 'layout', 'group_label' => '首页与布局'];
        }
        if (str_starts_with($rel, 'list/') || str_starts_with($rel, 'category/')) {
            return ['label' => $base, 'group' => 'list', 'group_label' => '列表'];
        }
        if (str_starts_with($rel, 'show/')) {
            return ['label' => $base, 'group' => 'show', 'group_label' => '详情'];
        }

        return ['label' => $base, 'group' => 'other', 'group_label' => '其他'];
    }

    protected function safePath(string $slug, string $rel): ?string
    {
        $rel = str_replace('\\', '/', trim($rel));
        if ($rel === '' || str_contains($rel, '..') || str_contains($rel, "\0") || str_starts_with($rel, '/')) {
            return null;
        }
        if (! $this->themes->isValidSlug($slug)) {
            return null;
        }
        $root = $this->themeDir($slug);
        $rootReal = realpath($root);
        if ($rootReal === false) {
            return null;
        }
        $full = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $parent = dirname($full);
        $parentReal = realpath($parent);
        if ($parentReal === false || ! $this->insideRoot($parentReal, $rootReal)) {
            return null;
        }

        return $full;
    }

    protected function insideRoot(string $path, string $root): bool
    {
        $path = strtolower(str_replace('\\', '/', $path));
        $root = strtolower(rtrim(str_replace('\\', '/', $root), '/'));

        return $path === $root || str_starts_with($path, $root.'/');
    }

    protected function themeDir(string $slug): string
    {
        return $this->themes->root().DIRECTORY_SEPARATOR.$slug;
    }

    protected function backupDir(string $slug): string
    {
        return storage_path('app/theme-backups'.DIRECTORY_SEPARATOR.$slug);
    }

    protected function latestBackupTime(string $slug, string $rel): int
    {
        $root = $this->backupDir($slug);
        if (! is_dir($root)) {
            return 0;
        }
        $latest = 0;
        $relPath = str_replace('/', DIRECTORY_SEPARATOR, $rel);
        foreach (File::directories($root) as $dir) {
            $cand = $dir.DIRECTORY_SEPARATOR.$relPath;
            if (is_file($cand)) {
                $latest = max($latest, (int) filemtime($cand));
            }
        }

        return $latest;
    }

    protected function clearCaches(string $slug): void
    {
        try {
            Artisan::call('view:clear');
        } catch (\Throwable) {
        }
        $this->htmlCache->forgetAll('template-edit:'.$slug);
    }
}
