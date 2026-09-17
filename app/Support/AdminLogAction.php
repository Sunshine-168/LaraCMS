<?php

namespace App\Support;

/** 把后台路径收成运营能看懂的一句话 */
class AdminLogAction
{
    public static function label(string $method, string $path, ?int $status = null): string
    {
        $method = strtoupper($method);
        $segments = self::segments($path);
        $joined = implode('/', $segments);
        if ($joined === 'login') {
            return ($status !== null && $status >= 400) ? '登录失败' : '登录了后台';
        }
        if ($joined === 'logout') {
            return '退出了登录';
        }
        $special = self::special($joined, $method);
        if ($special !== null) {
            return $special;
        }

        $resource = $segments[0] ?? '';
        $object = self::objects()[$resource] ?? null;
        $last = $segments[array_key_last($segments)] ?? '';

        if ($last === 'toggle') {
            return $object ? '启用或停用了'.$object : '启用或停用了一项';
        }
        if ($last === 'default') {
            return match ($resource) {
                'locales' => '设为默认语言',
                'sites' => '设为默认站点',
                default => $object ? '设为默认'.$object : '设为默认',
            };
        }
        if ($last === 'move') {
            return $object ? '调整了'.$object.'顺序' : '调整了顺序';
        }
        if ($last === 'batch') {
            return $object ? '批量处理了'.$object : '做了批量操作';
        }
        if (($segments[2] ?? '') === 'fields' || (($segments[0] ?? '') === 'models' && ($segments[1] ?? '') === 'fields')) {
            return match ($method) {
                'POST' => '新增了字段',
                'PUT', 'PATCH' => '保存了字段',
                'DELETE' => '删除了字段',
                default => '改了字段',
            };
        }

        $hasId = isset($segments[1]) && ctype_digit((string) $segments[1]);

        return match ($method) {
            'DELETE' => $object ? '删除了'.$object : '删除了记录',
            'PUT', 'PATCH' => $object ? '保存了'.$object : '保存了设置',
            'POST' => $hasId
                ? ($object ? '改了'.$object : '做了一次操作')
                : ($object ? '新增了'.$object : '提交了操作'),
            default => '做了一次操作',
        };
    }

    /** 路径里自带的对象名，如主题标识 */
    public static function pathSubject(string $path): ?string
    {
        $joined = implode('/', self::segments($path));
        if (preg_match('#^themes/(?!upload$)([^/]+)(?:/use|/templates(?:/.*)?)?$#', $joined, $m)) {
            return $m[1];
        }

        return null;
    }

    /** @return list<string> */
    private static function segments(string $path): array
    {
        $path = trim($path);
        $path = preg_replace('#^https?://[^/]+#', '', $path) ?? $path;
        $path = '/'.ltrim($path, '/');
        $path = preg_replace('#^/admin(?=/|$)#', '', $path) ?? $path;
        $path = trim($path, '/');

        return $path === '' ? [] : explode('/', $path);
    }

    private static function special(string $joined, string $method): ?string
    {
        $exact = [
            'media/upload' => '上传了文件',
            'media' => $method === 'POST' ? '新增了文件' : null,
            'entries/batch' => '批量处理了内容',
            'comments/batch' => '批量处理了评论',
            'recycle/batch' => '批量处理了回收站',
            'recycle/empty' => '清空了回收站',
            'cache/clear' => '清空了缓存',
            'cache/seed-demo' => '导入了演示数据',
            'cache/backup' => '做了备份',
            'html-cache/clear' => '清空了全页缓存',
            'html-cache/warm' => '预热了全页缓存',
            'html-cache/disk-build' => '生成了静态页',
            'html-cache/disk-step' => '生成静态页（进度）',
            'html-cache/disk-cancel' => '停止了静态页任务',
            'html-cache/disk-clear' => '删掉了静态页',
            'html-cache/disk' => '保存了磁盘静态页设置',
            'site/switch' => '切换了站点',
            'collectors/preview' => '试采了一下',
            'models/import' => '导入了内容模型',
            'settings' => '保存了站点设置',
            'html-cache' => '保存了全页缓存设置',
            'quick-panels' => '保存了快捷入口',
            'profile' => '保存了自己的名称',
            'profile/password' => '修改了自己的密码',
            'themes' => '复制了一套主题',
            'themes/upload' => '上传了主题包',
            'sitemap' => '保存了站点地图',
        ];
        if (isset($exact[$joined]) && $exact[$joined] !== null) {
            return $exact[$joined];
        }

        return match (true) {
            (bool) preg_match('#^entries/\d+/preview$#', $joined) => '预览了内容',
            (bool) preg_match('#^entries/\d+/revisions/\d+/restore$#', $joined) => '恢复了历史版本',
            (bool) preg_match('#^recycle/\d+/restore$#', $joined) => '从回收站恢复了内容',
            (bool) preg_match('#^recycle/\d+$#', $joined) && $method === 'DELETE' => '彻底删除了回收站内容',
            (bool) preg_match('#^cache/backups/.+/restore$#', $joined) => '还原了备份',
            (bool) preg_match('#^form-submissions/\d+/unread$#', $joined) => '把留言标成未读',
            (bool) preg_match('#^comments/\d+/moderate$#', $joined) => '审核了评论',
            (bool) preg_match('#^collectors/\d+/run$#', $joined) => '运行了采集',
            (bool) preg_match('#^models/\d+/copy$#', $joined) => '复制了内容模型',
            (bool) preg_match('#^themes/[^/]+/use$#', $joined) => '把主题用于本站',
            (bool) preg_match('#^themes/[^/]+/templates/save$#', $joined) => '保存了主题模板',
            (bool) preg_match('#^themes/[^/]+/templates/backup$#', $joined) => '备份了主题模板',
            (bool) preg_match('#^themes/[^/]+/templates/rollback$#', $joined) => '回滚了主题模板',
            $method === 'PUT' && (bool) preg_match('#^themes/[^/]+$#', $joined) => '保存了主题配置',
            $method === 'DELETE' && (bool) preg_match('#^themes/[^/]+$#', $joined) => '删除了主题',
            default => null,
        };
    }

    /** @return array{resource:string,object:?string,id:?int}|null */
    public static function target(string $path): ?array
    {
        $segments = self::segments($path);
        if ($segments === []) {
            return null;
        }
        $resource = $segments[0];
        $id = null;
        foreach ($segments as $seg) {
            if (ctype_digit((string) $seg)) {
                $id = (int) $seg;
                break;
            }
        }
        if ($resource === 'recycle') {
            $resource = 'entries';
        }

        return [
            'resource' => $resource,
            'object' => self::objects()[$resource] ?? self::objects()[$segments[0]] ?? null,
            'id' => $id,
        ];
    }

    public static function editUrl(string $resource, ?int $id): ?string
    {
        if (! $id) {
            return null;
        }
        $named = [
            'tags' => 'admin.tags.edit',
            'entries' => 'admin.entries.edit',
            'recycle' => 'admin.entries.edit',
            'categories' => 'admin.categories.edit',
            'members' => 'admin.members.edit',
            'admin-users' => 'admin.admin-users.edit',
            'sites' => 'admin.sites.edit',
            'locales' => 'admin.locales.edit',
            'redirects' => 'admin.redirects.edit',
            'forms' => 'admin.forms.edit',
            'form-submissions' => 'admin.form-submissions.show',
            'collectors' => 'admin.collectors.edit',
            'menus' => 'admin.menus.edit',
            'flags' => 'admin.flags.edit',
            'recommends' => 'admin.recommends.edit',
            'friend-links' => 'admin.friend-links.edit',
            'models' => 'admin.models.edit',
            'roles' => 'admin.roles.edit',
            'member-groups' => 'admin.member-groups.edit',
        ][$resource] ?? null;
        if ($named === null) {
            return null;
        }
        try {
            return route($named, $id);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $fileNames
     */
    public static function summarize(array $input, array $fileNames = []): ?string
    {
        $parts = [];
        $name = self::firstText($input['title'] ?? null)
            ?? self::localeTitle($input['locales'] ?? null)
            ?? self::firstText($input['name'] ?? null)
            ?? self::firstText($input['source_path'] ?? null)
            ?? self::firstText($input['email'] ?? null)
            ?? self::firstText($input['slug'] ?? null)
            ?? self::firstText($input['label'] ?? null)
            ?? self::firstText($input['site_name'] ?? null);
        if ($name !== null) {
            $parts[] = $name;
        }

        $ids = $input['ids'] ?? null;
        if (is_array($ids) && $ids !== []) {
            $parts[] = '选了 '.count($ids).' 条';
        }

        $action = self::actionLabel($input['action'] ?? null);
        if ($action !== null) {
            $parts[] = $action;
        }

        $status = self::statusLabel($input['status'] ?? null);
        if ($status !== null) {
            $parts[] = $status;
        }

        $tab = self::tabLabel($input['tab'] ?? null);
        if ($tab !== null) {
            $parts[] = $tab;
        }

        $from = self::firstText($input['from'] ?? null);
        if ($from !== null && $from !== ($input['slug'] ?? '')) {
            $parts[] = '从 '.$from.' 复制';
        }

        foreach ([
            'html_cache_enabled' => ['开启全页缓存', '关闭全页缓存'],
            'disk_html_enabled' => ['打开磁盘静态页', '关闭磁盘静态页'],
            'site_closed' => ['暂时关站', '打开网站'],
        ] as $key => [$on, $off]) {
            if (array_key_exists($key, $input)) {
                $parts[] = self::flagOn($input[$key]) ? $on : $off;
            }
        }

        $file = $fileNames[0] ?? null;
        if (is_string($file) && trim($file) !== '') {
            $parts[] = trim($file);
        }

        $parts = array_values(array_unique(array_filter($parts)));
        if ($parts === []) {
            return null;
        }

        return mb_substr(implode(' · ', $parts), 0, 120);
    }

    private static function localeTitle(mixed $locales): ?string
    {
        if (! is_array($locales)) {
            return null;
        }
        foreach ($locales as $pack) {
            if (! is_array($pack)) {
                continue;
            }
            $title = self::firstText($pack['title'] ?? null);
            if ($title !== null) {
                return $title;
            }
        }

        return null;
    }

    private static function actionLabel(mixed $action): ?string
    {
        $action = is_string($action) ? trim($action) : '';
        if ($action === '') {
            return null;
        }

        return match ($action) {
            'publish' => '发布到前台',
            'pending' => '改为待审',
            'draft' => '改回草稿',
            'delete' => '删除',
            'move' => '移动栏目',
            'copy' => '复制一份',
            'push' => '推送到推荐位',
            'restore' => '还原',
            'approved' => '通过',
            'rejected' => '拒绝',
            'spam' => '标为垃圾',
            default => null,
        };
    }

    private static function statusLabel(mixed $status): ?string
    {
        $status = is_string($status) ? trim($status) : '';
        if ($status === '') {
            return null;
        }

        return match ($status) {
            'draft' => '草稿',
            'published' => '已发布',
            'pending' => '待审',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'spam' => '垃圾',
            default => null,
        };
    }

    private static function tabLabel(mixed $tab): ?string
    {
        $tab = is_string($tab) ? trim($tab) : '';

        return match ($tab) {
            'site' => '网站',
            'look' => '外观',
            'interact' => '评论',
            'more' => '更多',
            default => null,
        };
    }

    private static function flagOn(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
    }

    private static function firstText(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $found = self::firstText($item);
                if ($found !== null) {
                    return $found;
                }
            }

            return null;
        }
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /** @return array<string, string> */
    private static function objects(): array
    {
        return [
            'entries' => '内容',
            'categories' => '栏目',
            'tags' => '标签',
            'flags' => '推荐属性',
            'forms' => '表单',
            'form-submissions' => '留言',
            'comments' => '评论',
            'redirects' => '跳转',
            'menus' => '导航',
            'media' => '文件',
            'models' => '内容模型',
            'recommends' => '推荐位',
            'friend-links' => '友链',
            'sitemap' => '站点地图',
            'members' => '会员',
            'member-groups' => '会员分组',
            'collectors' => '采集源',
            'sites' => '站点',
            'locales' => '语言',
            'admin-users' => '管理员',
            'roles' => '角色',
            'recycle' => '回收站内容',
            'help' => '说明',
            'html-cache' => '全页缓存',
            'cache' => '缓存',
            'themes' => '主题',
            'settings' => '站点设置',
            'profile' => '账号',
            'quick-panels' => '快捷入口',
        ];
    }
}
