<?php

namespace App\Support;

/** 把后台路径收成运营能看懂的一句话 */
class AdminLogAction
{
    public static function label(string $method, string $path): string
    {
        $method = strtoupper($method);
        $segments = self::segments($path);
        $special = self::special(implode('/', $segments));
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
            return '调整了顺序';
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
            'DELETE' => $object ? '删除了'.$object : '删除了一项',
            'PUT', 'PATCH' => $object ? '保存了'.$object : '保存了设置',
            'POST' => $hasId
                ? ($object ? '改了'.$object : '做了一次操作')
                : ($object ? '新增了'.$object : '新增了一项'),
            default => '做了一次操作',
        };
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

    private static function special(string $joined): ?string
    {
        $exact = [
            'media/upload' => '上传了文件',
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
            'html-cache/disk-clear' => '删掉了静态页',
            'site/switch' => '切换了站点',
            'collectors/preview' => '试采了一下',
            'models/import' => '导入了内容模型',
            'settings' => '保存了站点设置',
            'html-cache' => '保存了全页缓存设置',
            'quick-panels' => '保存了快捷入口',
        ];
        if (isset($exact[$joined])) {
            return $exact[$joined];
        }

        return match (true) {
            (bool) preg_match('#^entries/\d+/preview$#', $joined) => '预览了内容',
            (bool) preg_match('#^entries/\d+/revisions/\d+/restore$#', $joined) => '恢复了历史版本',
            (bool) preg_match('#^recycle/\d+/restore$#', $joined) => '从回收站恢复了内容',
            (bool) preg_match('#^cache/backups/.+/restore$#', $joined) => '还原了备份',
            (bool) preg_match('#^form-submissions/\d+/unread$#', $joined) => '把留言标成未读',
            (bool) preg_match('#^comments/\d+/moderate$#', $joined) => '审核了评论',
            (bool) preg_match('#^collectors/\d+/run$#', $joined) => '运行了采集',
            (bool) preg_match('#^models/\d+/copy$#', $joined) => '复制了内容模型',
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

        return [
            'resource' => $resource,
            'object' => self::objects()[$resource] ?? null,
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
        $text = self::firstText($input['title'] ?? null)
            ?? self::firstText($input['name'] ?? null)
            ?? self::firstText($input['source_path'] ?? null)
            ?? self::firstText($input['email'] ?? null)
            ?? self::firstText($input['slug'] ?? null)
            ?? self::firstText($input['label'] ?? null);
        if ($text !== null) {
            return mb_substr($text, 0, 80);
        }
        $ids = $input['ids'] ?? null;
        if (is_array($ids) && $ids !== []) {
            return '选了 '.count($ids).' 条';
        }
        $file = $fileNames[0] ?? null;
        if (is_string($file) && trim($file) !== '') {
            return mb_substr(trim($file), 0, 80);
        }

        return null;
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
            'members' => '会员',
            'member-groups' => '会员分组',
            'collectors' => '采集源',
            'sites' => '站点',
            'locales' => '语言',
            'admin-users' => '管理员',
            'roles' => '角色',
            'recycle' => '回收站内容',
            'help' => '说明',
        ];
    }
}
