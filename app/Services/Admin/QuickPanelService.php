<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminQuickPanel;
use App\Models\Admin\AdminUser;
use App\Support\Result;
use Illuminate\Support\Facades\DB;

class QuickPanelService
{
    /**
     * 可选快捷项目录（key => 定义）
     *
     * @return array<string, array{title:string,icon:string,route:?string,url:?string,perm:?string}>
     */
    public function catalog(): array
    {
        return [
            'entries' => ['title' => '内容', 'icon' => 'fas fa-file-alt', 'route' => 'admin.entries.index', 'url' => null, 'perm' => 'entries'],
            'categories' => ['title' => '栏目', 'icon' => 'fas fa-sitemap', 'route' => 'admin.categories.index', 'url' => null, 'perm' => 'categories'],
            'media' => ['title' => '媒体', 'icon' => 'fas fa-photo-video', 'route' => 'admin.media.index', 'url' => null, 'perm' => 'media'],
            'forms' => ['title' => '表单', 'icon' => 'fas fa-clipboard-list', 'route' => 'admin.forms.index', 'url' => null, 'perm' => 'forms'],
            'form_submissions' => ['title' => '留言', 'icon' => 'fas fa-inbox', 'route' => 'admin.form-submissions.index', 'url' => null, 'perm' => 'form_submissions'],
            'comments' => ['title' => '评论', 'icon' => 'fas fa-comments', 'route' => 'admin.comments.index', 'url' => null, 'perm' => 'comments'],
            'stats' => ['title' => '统计', 'icon' => 'fas fa-chart-line', 'route' => 'admin.stats.index', 'url' => null, 'perm' => 'stats'],
            'menus' => ['title' => '菜单', 'icon' => 'fas fa-bars', 'route' => 'admin.menus.index', 'url' => null, 'perm' => 'menus'],
            'tags' => ['title' => '标签', 'icon' => 'fas fa-tags', 'route' => 'admin.tags.index', 'url' => null, 'perm' => 'tags'],
            'recommends' => ['title' => '推荐位', 'icon' => 'fas fa-th-large', 'route' => 'admin.recommends.index', 'url' => null, 'perm' => 'recommends'],
            'models' => ['title' => '模型', 'icon' => 'fas fa-cube', 'route' => 'admin.models.index', 'url' => null, 'perm' => 'models'],
            'collectors' => ['title' => '采集', 'icon' => 'fas fa-cloud-download-alt', 'route' => 'admin.collectors.index', 'url' => null, 'perm' => 'collectors'],
            'members' => ['title' => '会员', 'icon' => 'fas fa-users', 'route' => 'admin.members.index', 'url' => null, 'perm' => 'members'],
            'redirects' => ['title' => '重定向', 'icon' => 'fas fa-exchange-alt', 'route' => 'admin.redirects.index', 'url' => null, 'perm' => 'redirects'],
            'html_cache' => ['title' => '缓存', 'icon' => 'fas fa-bolt', 'route' => 'admin.html-cache.edit', 'url' => null, 'perm' => 'html_cache'],
            'settings' => ['title' => '设置', 'icon' => 'fas fa-cog', 'route' => 'admin.settings.edit', 'url' => null, 'perm' => 'settings'],
            'sites' => ['title' => '站点', 'icon' => 'fas fa-globe', 'route' => 'admin.sites.index', 'url' => null, 'perm' => 'sites'],
            'locales' => ['title' => '语言', 'icon' => 'fas fa-language', 'route' => 'admin.locales.index', 'url' => null, 'perm' => 'locales'],
            'front' => ['title' => '前台', 'icon' => 'fas fa-home', 'route' => null, 'url' => url('/'), 'perm' => null],
        ];
    }

    /** @return list<string> */
    public function defaultKeys(AdminUser $admin): array
    {
        $keys = ['entries', 'categories', 'media', 'forms', 'form_submissions', 'stats', 'menus', 'front'];
        if ($admin->isSuperAdmin() || $admin->canPerm('models')) {
            $keys = array_merge($keys, ['recommends', 'models', 'collectors', 'html_cache', 'settings']);
        }

        return array_values(array_filter($keys, fn ($k) => $this->canUse($admin, $k)));
    }

    public function canUse(AdminUser $admin, string $key): bool
    {
        $item = $this->catalog()[$key] ?? null;
        if (! $item) {
            return false;
        }
        if ($item['perm'] === null) {
            return true;
        }

        return $admin->canPerm($item['perm']);
    }

    /** @return list<array{key:string,title:string,icon:string,url:string}> */
    public function forAdmin(AdminUser $admin): array
    {
        $catalog = $this->catalog();
        $saved = AdminQuickPanel::query()
            ->where('admin_user_id', $admin->id)
            ->orderBy('sort')
            ->orderBy('id')
            ->pluck('item_key')
            ->all();

        $keys = $saved !== [] ? $saved : $this->defaultKeys($admin);
        $out = [];
        foreach ($keys as $key) {
            if (! $this->canUse($admin, $key)) {
                continue;
            }
            $item = $catalog[$key];
            $url = $item['url'] ?? null;
            if (! $url && ! empty($item['route'])) {
                try {
                    $url = route($item['route']);
                } catch (\Throwable) {
                    continue;
                }
            }
            if (! $url) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'title' => $item['title'],
                'icon' => $item['icon'],
                'url' => $url,
            ];
        }

        return $out;
    }

    /** @param  list<string>  $keys */
    public function saveForAdmin(AdminUser $admin, array $keys): array
    {
        $catalog = $this->catalog();
        $keys = array_values(array_unique(array_filter($keys, fn ($k) => is_string($k) && isset($catalog[$k]) && $this->canUse($admin, $k))));

        DB::transaction(function () use ($admin, $keys) {
            AdminQuickPanel::query()->where('admin_user_id', $admin->id)->delete();
            foreach ($keys as $i => $key) {
                AdminQuickPanel::query()->create([
                    'admin_user_id' => $admin->id,
                    'item_key' => $key,
                    'sort' => $i * 10,
                ]);
            }
        });

        return Result::ok(null, $keys === [] ? '已恢复为默认快捷面板（未勾选时）' : '快捷面板已保存');
    }

    /** @return list<array{key:string,title:string,icon:string,selected:bool}> */
    public function editOptions(AdminUser $admin): array
    {
        $saved = AdminQuickPanel::query()
            ->where('admin_user_id', $admin->id)
            ->orderBy('sort')
            ->pluck('item_key')
            ->all();
        $selected = $saved !== [] ? $saved : $this->defaultKeys($admin);
        $out = [];
        foreach ($this->catalog() as $key => $item) {
            if (! $this->canUse($admin, $key)) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'title' => $item['title'],
                'icon' => $item['icon'],
                'selected' => in_array($key, $selected, true),
            ];
        }

        return $out;
    }
}
