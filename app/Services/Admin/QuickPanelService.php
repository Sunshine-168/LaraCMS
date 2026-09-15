<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminQuickPanel;
use App\Models\Admin\AdminUser;
use App\Models\Cms\ContentModel;
use App\Support\Result;
use Illuminate\Support\Facades\DB;

class QuickPanelService
{
    /**
     * 可选快捷项目录（key => 定义）
     *
     * @return array<string, array{title:string,icon:string,route:?string,url:?string,perm:?string,query?:array<string, mixed>}>
     */
    public function catalog(): array
    {
        return [
            'entries_write' => ['title' => '写内容', 'icon' => 'fas fa-pen', 'route' => 'admin.entries.create', 'url' => null, 'perm' => 'entries'],
            'entries_review' => ['title' => '审稿', 'icon' => 'fas fa-check-double', 'route' => 'admin.entries.index', 'url' => null, 'perm' => 'entries', 'query' => ['status' => 'pending']],
            'form_submissions' => ['title' => '收件箱', 'icon' => 'fas fa-inbox', 'route' => 'admin.form-submissions.index', 'url' => null, 'perm' => 'form_submissions', 'query' => ['status' => 'unread']],
            'comments' => ['title' => '审评论', 'icon' => 'fas fa-comments', 'route' => 'admin.comments.index', 'url' => null, 'perm' => 'comments', 'query' => ['status' => 'pending']],
            'media' => ['title' => '媒体上传', 'icon' => 'fas fa-photo-video', 'route' => 'admin.media.index', 'url' => null, 'perm' => 'media'],
            'stats' => ['title' => '看流量', 'icon' => 'fas fa-chart-line', 'route' => 'admin.stats.index', 'url' => null, 'perm' => 'stats'],
            'front' => ['title' => '看前台', 'icon' => 'fas fa-home', 'route' => null, 'url' => url('/'), 'perm' => null],
            'settings' => ['title' => '设置', 'icon' => 'fas fa-cog', 'route' => 'admin.settings.edit', 'url' => null, 'perm' => 'settings'],
            'entries' => ['title' => '内容', 'icon' => 'fas fa-file-alt', 'route' => 'admin.entries.index', 'url' => null, 'perm' => 'entries'],
            'categories' => ['title' => '栏目', 'icon' => 'fas fa-sitemap', 'route' => 'admin.categories.index', 'url' => null, 'perm' => 'categories'],
            'forms' => ['title' => '表单', 'icon' => 'fas fa-clipboard-list', 'route' => 'admin.forms.index', 'url' => null, 'perm' => 'forms'],
            'menus' => ['title' => '导航', 'icon' => 'fas fa-bars', 'route' => 'admin.menus.index', 'url' => null, 'perm' => 'menus'],
            'tags' => ['title' => '标签', 'icon' => 'fas fa-tags', 'route' => 'admin.tags.index', 'url' => null, 'perm' => 'tags'],
            'recommends' => ['title' => '推荐位', 'icon' => 'fas fa-th-large', 'route' => 'admin.recommends.index', 'url' => null, 'perm' => 'recommends'],
            'models' => ['title' => '模型', 'icon' => 'fas fa-cube', 'route' => 'admin.models.index', 'url' => null, 'perm' => 'models'],
            'collectors' => ['title' => '采集', 'icon' => 'fas fa-cloud-download-alt', 'route' => 'admin.collectors.index', 'url' => null, 'perm' => 'collectors'],
            'members' => ['title' => '会员', 'icon' => 'fas fa-users', 'route' => 'admin.members.index', 'url' => null, 'perm' => 'members'],
            'redirects' => ['title' => '跳转', 'icon' => 'fas fa-exchange-alt', 'route' => 'admin.redirects.index', 'url' => null, 'perm' => 'redirects'],
            'friend_links' => ['title' => '友链', 'icon' => 'fas fa-link', 'route' => 'admin.friend-links.index', 'url' => null, 'perm' => 'friend_links'],
            'sitemap' => ['title' => '地图', 'icon' => 'fas fa-map', 'route' => 'admin.sitemap.edit', 'url' => null, 'perm' => 'sitemap'],
            'recycle' => ['title' => '回收站', 'icon' => 'fas fa-trash-alt', 'route' => 'admin.recycle.index', 'url' => null, 'perm' => 'recycle'],
            'html_cache' => ['title' => '缓存', 'icon' => 'fas fa-bolt', 'route' => 'admin.html-cache.edit', 'url' => null, 'perm' => 'html_cache'],
            'sites' => ['title' => '站点', 'icon' => 'fas fa-globe', 'route' => 'admin.sites.index', 'url' => null, 'perm' => 'sites'],
            'locales' => ['title' => '语言', 'icon' => 'fas fa-language', 'route' => 'admin.locales.index', 'url' => null, 'perm' => 'locales'],
        ];
    }

    /** @return list<string> */
    public function defaultKeys(AdminUser $admin): array
    {
        if ($admin->isSuperAdmin()) {
            $keys = ['entries_write', 'entries_review', 'form_submissions', 'media', 'front', 'settings'];
        } elseif ($admin->canPerm('entries')) {
            $keys = ['entries_write', 'entries_review', 'form_submissions', 'comments', 'media', 'front'];
        } else {
            $keys = ['stats', 'form_submissions', 'comments', 'media', 'front'];
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
            if ($key === 'entries_write') {
                $modelId = ContentModel::query()->where('is_active', true)->orderBy('sort_order')->value('id');
                if ($modelId) {
                    $item['query'] = ['model_id' => $modelId];
                }
            }
            if (! $url && ! empty($item['route'])) {
                try {
                    $url = route($item['route'], $item['query'] ?? []);
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
