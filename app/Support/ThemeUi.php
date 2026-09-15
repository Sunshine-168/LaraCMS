<?php

namespace App\Support;

use App\Models\Cms\Setting;

/** 主题里搜索、登录等固定短句，按内容语言切换 */
class ThemeUi
{
    /** @return array<string, array{label: string, zh_cn: string, en: string}> */
    public static function catalog(): array
    {
        return [
            'search' => ['label' => '搜索', 'zh_cn' => '搜索', 'en' => 'Search'],
            'login' => ['label' => '登录', 'zh_cn' => '登录', 'en' => 'Log in'],
            'register' => ['label' => '注册', 'zh_cn' => '注册', 'en' => 'Register'],
            'member' => ['label' => '会员', 'zh_cn' => '会员', 'en' => 'Member'],
            'logout' => ['label' => '退出', 'zh_cn' => '退出', 'en' => 'Log out'],
            'home' => ['label' => '首页', 'zh_cn' => '首页', 'en' => 'Home'],
            'keywords' => ['label' => '关键词', 'zh_cn' => '关键词', 'en' => 'Keywords'],
            'all_models' => ['label' => '全部模型', 'zh_cn' => '全部模型', 'en' => 'All models'],
            'all_categories' => ['label' => '全部栏目', 'zh_cn' => '全部栏目', 'en' => 'All categories'],
            'no_results' => ['label' => '无结果', 'zh_cn' => '没有结果', 'en' => 'No results'],
            'views' => ['label' => '浏览', 'zh_cn' => '浏览', 'en' => 'Views'],
            'prev' => ['label' => '上一篇', 'zh_cn' => '上一篇', 'en' => 'Previous'],
            'next' => ['label' => '下一篇', 'zh_cn' => '下一篇', 'en' => 'Next'],
            'prev_page' => ['label' => '上一页', 'zh_cn' => '上一页', 'en' => 'Previous'],
            'next_page' => ['label' => '下一页', 'zh_cn' => '下一页', 'en' => 'Next'],
            'not_found' => ['label' => '404', 'zh_cn' => '页面不存在', 'en' => 'Page not found'],
            'not_found_hint' => ['label' => '404说明', 'zh_cn' => '链接可能写错了，或内容已下线。', 'en' => 'This link may be wrong, or the content is gone.'],
            'back_home' => ['label' => '回首页', 'zh_cn' => '回首页', 'en' => 'Back home'],
            'comments' => ['label' => '评论', 'zh_cn' => '评论', 'en' => 'Comments'],
            'comments_empty' => ['label' => '无评论', 'zh_cn' => '暂无评论，来抢沙发吧。', 'en' => 'No comments yet.'],
            'comment_name' => ['label' => '昵称', 'zh_cn' => '昵称', 'en' => 'Name'],
            'comment_email' => ['label' => '评论邮箱', 'zh_cn' => '邮箱（可选）', 'en' => 'Email (optional)'],
            'comment_body' => ['label' => '评论内容', 'zh_cn' => '评论内容', 'en' => 'Comment'],
            'comment_submit' => ['label' => '提交评论', 'zh_cn' => '提交评论', 'en' => 'Post comment'],
            'comment_as_member' => ['label' => '会员评论', 'zh_cn' => '以会员身份评论：:name', 'en' => 'Commenting as :name'],
            'comment_login' => ['label' => '评论需登录', 'zh_cn' => '请 :link 后发表评论。', 'en' => 'Please :link to comment.'],
            'captcha' => ['label' => '验证码', 'zh_cn' => '验证码', 'en' => 'Captcha'],
            'favorite' => ['label' => '收藏', 'zh_cn' => '收藏本文', 'en' => 'Save'],
            'unfavorite' => ['label' => '取消收藏', 'zh_cn' => '取消收藏', 'en' => 'Saved'],
            'favorite_login' => ['label' => '收藏需登录', 'zh_cn' => ':link 后可收藏本文', 'en' => ':link to save this'],
            'prev_chapter' => ['label' => '上一话', 'zh_cn' => '上一话', 'en' => 'Previous chapter'],
            'next_chapter' => ['label' => '下一话', 'zh_cn' => '下一话', 'en' => 'Next chapter'],
            'back_comics' => ['label' => '返回漫画库', 'zh_cn' => '返回漫画库', 'en' => 'Back to comics'],
            'member_login' => ['label' => '会员登录', 'zh_cn' => '会员登录', 'en' => 'Member login'],
            'member_register' => ['label' => '会员注册', 'zh_cn' => '会员注册', 'en' => 'Register'],
            'member_center' => ['label' => '会员中心', 'zh_cn' => '会员中心', 'en' => 'Member area'],
            'email' => ['label' => '邮箱', 'zh_cn' => '邮箱', 'en' => 'Email'],
            'password' => ['label' => '密码', 'zh_cn' => '密码', 'en' => 'Password'],
            'password_confirm' => ['label' => '确认密码', 'zh_cn' => '确认密码', 'en' => 'Confirm password'],
            'remember' => ['label' => '记住我', 'zh_cn' => '记住我', 'en' => 'Remember me'],
            'forgot_password' => ['label' => '忘记密码', 'zh_cn' => '忘记密码', 'en' => 'Forgot password'],
            'forgot_title' => ['label' => '找回密码', 'zh_cn' => '找回密码', 'en' => 'Reset password'],
            'forgot_send' => ['label' => '发重置信', 'zh_cn' => '生成重置链接', 'en' => 'Send reset link'],
            'register_account' => ['label' => '注册账号', 'zh_cn' => '注册账号', 'en' => 'Create account'],
            'have_account' => ['label' => '已有账号', 'zh_cn' => '已有账号？登录', 'en' => 'Already have an account? Log in'],
            'back_login' => ['label' => '返回登录', 'zh_cn' => '返回登录', 'en' => 'Back to sign in'],
            'nickname' => ['label' => '昵称', 'zh_cn' => '昵称', 'en' => 'Display name'],
            'reset_password' => ['label' => '设置新密码', 'zh_cn' => '设置新密码', 'en' => 'Set a new password'],
            'new_password' => ['label' => '新密码', 'zh_cn' => '新密码', 'en' => 'New password'],
            'save' => ['label' => '保存', 'zh_cn' => '保存', 'en' => 'Save'],
            'hello' => ['label' => '问候', 'zh_cn' => '你好，:name', 'en' => 'Hello, :name'],
            'profile' => ['label' => '资料', 'zh_cn' => '资料', 'en' => 'Profile'],
            'save_profile' => ['label' => '保存资料', 'zh_cn' => '保存资料', 'en' => 'Save profile'],
            'change_password' => ['label' => '修改密码', 'zh_cn' => '修改密码', 'en' => 'Change password'],
            'current_password' => ['label' => '当前密码', 'zh_cn' => '当前密码', 'en' => 'Current password'],
            'save_password' => ['label' => '保存密码', 'zh_cn' => '保存密码', 'en' => 'Save password'],
            'my_comments' => ['label' => '我的评论', 'zh_cn' => '我的评论', 'en' => 'My comments'],
            'my_favorites' => ['label' => '我的收藏', 'zh_cn' => '我的收藏', 'en' => 'Saved'],
            'my_posts' => ['label' => '我的投稿', 'zh_cn' => '我的投稿', 'en' => 'My submissions'],
            'member_content' => ['label' => '会员内容', 'zh_cn' => '会员内容', 'en' => 'Member content'],
            'avatar' => ['label' => '头像', 'zh_cn' => '头像 URL', 'en' => 'Avatar URL'],
            'phone' => ['label' => '手机', 'zh_cn' => '手机', 'en' => 'Phone'],
            'bio' => ['label' => '简介', 'zh_cn' => '简介', 'en' => 'Bio'],
            'group' => ['label' => '分组', 'zh_cn' => '分组', 'en' => 'Group'],
        ];
    }

    /** @return array<string, array<string, string>> */
    public static function stored(): array
    {
        try {
            $raw = Setting::getValue('theme_ui', '');
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

    public static function get(string $key): string
    {
        $locale = Locale::current();
        $catalog = self::catalog();
        $stored = self::stored();
        $fallback = $catalog[$key]['zh_cn'] ?? $key;
        $fromStore = $stored[$key][$locale] ?? null;
        if (is_string($fromStore) && trim($fromStore) !== '') {
            return $fromStore;
        }
        $fromCatalog = $catalog[$key][$locale] ?? null;
        if (is_string($fromCatalog) && $fromCatalog !== '') {
            return $fromCatalog;
        }
        $default = Locale::default();
        $fromDefault = $stored[$key][$default] ?? $catalog[$key][$default] ?? $catalog[$key]['en'] ?? null;
        if (is_string($fromDefault) && $fromDefault !== '') {
            return $fromDefault;
        }

        return $fallback;
    }

    /** @param  array<string, array<string, string>>  $input */
    public static function save(array $input): void
    {
        $prev = self::stored();
        $codes = Locale::configuredCodes();
        if ($codes === []) {
            $codes = Locale::codes();
        }
        $out = [];
        foreach (self::catalog() as $key => $meta) {
            $row = is_array($prev[$key] ?? null) ? $prev[$key] : [];
            foreach ($codes as $code) {
                if (isset($input[$key]) && array_key_exists($code, $input[$key])) {
                    $value = trim((string) $input[$key][$code]);
                    $row[$code] = $value !== '' ? $value : (string) ($meta[$code] ?? $meta['zh_cn']);
                } elseif (! isset($row[$code]) || trim((string) $row[$code]) === '') {
                    $row[$code] = (string) ($meta[$code] ?? $meta['zh_cn']);
                }
            }
            $out[$key] = $row;
        }
        Setting::setValue('theme_ui', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
