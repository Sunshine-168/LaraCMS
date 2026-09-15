<?php

namespace Database\Seeders;

use App\Models\Admin\AdminPermission;
use App\Models\Admin\AdminRole;
use App\Models\Cms\ContentModel;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\ModelField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AdminRbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['key' => 'dashboard', 'name' => '仪表盘', 'group' => '概览', 'sort' => 10],
            ['key' => 'stats', 'name' => '流量统计', 'group' => '概览', 'sort' => 20],
            ['key' => 'entries', 'name' => '内容管理', 'group' => '内容', 'sort' => 30],
            ['key' => 'publish', 'name' => '发布内容', 'group' => '内容', 'sort' => 35],
            ['key' => 'recycle', 'name' => '回收站', 'group' => '内容', 'sort' => 40],
            ['key' => 'categories', 'name' => '栏目管理', 'group' => '内容', 'sort' => 50],
            ['key' => 'tags', 'name' => '标签管理', 'group' => '内容', 'sort' => 60],
            ['key' => 'flags', 'name' => '推荐属性', 'group' => '内容', 'sort' => 65],
            ['key' => 'recommends', 'name' => '推荐位', 'group' => '内容', 'sort' => 70],
            ['key' => 'models', 'name' => '内容模型', 'group' => '内容', 'sort' => 80],
            ['key' => 'media', 'name' => '媒体库', 'group' => '内容', 'sort' => 90],
            ['key' => 'menus', 'name' => '导航菜单', 'group' => '内容', 'sort' => 100],
            ['key' => 'forms', 'name' => '表单定义', 'group' => '内容', 'sort' => 110],
            ['key' => 'form_submissions', 'name' => '留言收件箱', 'group' => '内容', 'sort' => 120],
            ['key' => 'comments', 'name' => '评论审核', 'group' => '内容', 'sort' => 125],
            ['key' => 'friend_links', 'name' => '友链', 'group' => '内容', 'sort' => 126],
            ['key' => 'redirects', 'name' => '重定向', 'group' => '运营', 'sort' => 128],
            ['key' => 'collectors', 'name' => '采集任务', 'group' => '运营', 'sort' => 130],
            ['key' => 'members', 'name' => '会员管理', 'group' => '运营', 'sort' => 140],
            ['key' => 'member_groups', 'name' => '会员分组', 'group' => '运营', 'sort' => 150],
            ['key' => 'html_cache', 'name' => '全页缓存', 'group' => '运营', 'sort' => 160],
            ['key' => 'sites', 'name' => '站点管理', 'group' => '系统', 'sort' => 165],
            ['key' => 'settings', 'name' => '站点设置', 'group' => '系统', 'sort' => 170],
            ['key' => 'locales', 'name' => '语言管理', 'group' => '系统', 'sort' => 175],
            ['key' => 'logs', 'name' => '操作日志', 'group' => '系统', 'sort' => 180],
            ['key' => 'admin_users', 'name' => '管理员', 'group' => '系统', 'sort' => 190],
            ['key' => 'roles', 'name' => '角色权限', 'group' => '系统', 'sort' => 200],
        ];

        $ids = [];
        foreach ($permissions as $row) {
            $perm = AdminPermission::query()->updateOrCreate(['key' => $row['key']], $row);
            $ids[$row['key']] = $perm->id;
        }

        $super = AdminRole::query()->updateOrCreate(
            ['slug' => 'super_admin'],
            ['name' => '超级管理员', 'is_super' => true, 'remark' => '拥有全部权限']
        );
        $editor = AdminRole::query()->updateOrCreate(
            ['slug' => 'editor'],
            ['name' => '编辑', 'is_super' => false, 'remark' => '内容与运营基础权限']
        );
        $operator = AdminRole::query()->updateOrCreate(
            ['slug' => 'operator'],
            ['name' => '运营', 'is_super' => false, 'remark' => '统计与留言处理']
        );

        $editorKeys = [
            'dashboard', 'stats', 'entries', 'recycle', 'categories', 'tags', 'flags',
            'media', 'menus', 'forms', 'form_submissions', 'comments', 'friend_links', 'redirects',
        ];
        $operatorKeys = ['dashboard', 'stats', 'form_submissions', 'comments', 'redirects', 'media'];

        $editor->permissions()->sync(array_values(array_intersect_key($ids, array_flip($editorKeys))));
        $operator->permissions()->sync(array_values(array_intersect_key($ids, array_flip($operatorKeys))));
        $super->permissions()->detach(); // 超管用 is_super，无需挂权限

        $this->enableContributeOnExistingSites();
    }

    /** 给已有站点补上投稿权限与文章模型开关 */
    protected function enableContributeOnExistingSites(): void
    {
        if (! Schema::hasTable('cms_member_groups')) {
            return;
        }
        foreach (MemberGroup::query()->withoutGlobalScopes()->get() as $group) {
            $perms = $group->permissions;
            if (! is_array($perms) || $perms === []) {
                continue;
            }
            if (! in_array('contribute', $perms, true)) {
                $perms[] = 'contribute';
                $group->permissions = array_values(array_unique($perms));
                $group->save();
            }
        }
        if (Schema::hasColumn('cms_models', 'allow_contribute')) {
            ContentModel::query()->withoutGlobalScopes()
                ->where('slug', 'article')
                ->update(['allow_contribute' => true]);
            foreach (ContentModel::query()->withoutGlobalScopes()->where('slug', 'article')->get() as $article) {
                ModelField::query()->firstOrCreate(
                    ['model_id' => $article->id, 'name' => 'source_note'],
                    ['label' => '来源', 'type' => 'text', 'sort_order' => 0]
                );
            }
        }
    }
}
