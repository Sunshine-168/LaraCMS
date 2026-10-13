<?php

namespace App\Support;

class OfficialNews
{
    /** @return list<array{slug: string, date: string, title_zh: string, title_en: string, excerpt_zh: string, excerpt_en: string, body_zh: string, body_en: string}> */
    public static function all(): array
    {
        return [
            [
                'slug' => 'open-source',
                'date' => '2026-10-12',
                'title_zh' => 'LeafCMS 源码开源',
                'title_en' => 'LeafCMS source is public',
                'excerpt_zh' => '仓库在 Sunshine-168/LeafCMS，MIT 许可，默认分支 main。',
                'excerpt_en' => 'The repo is Sunshine-168/LeafCMS, MIT, default branch main.',
                'body_zh' => '<p>开源仓库是 <a href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">Sunshine-168/LeafCMS</a>。MIT 许可，可以商用和二次开发。</p><p>需要 PHP 8.3 及以上，内核是 Laravel 13。试用可以用 SQLite，也可以改成 MySQL。</p><pre><code>git clone https://github.com/Sunshine-168/LeafCMS.git
cd LeafCMS</code></pre>',
                'body_en' => '<p>The public repo is <a href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">Sunshine-168/LeafCMS</a>. MIT, including commercial use and forks.</p><p>It needs PHP 8.3 or newer and runs on Laravel 13. SQLite is enough to try it; MySQL works too.</p><pre><code>git clone https://github.com/Sunshine-168/LeafCMS.git
cd LeafCMS</code></pre>',
            ],
            [
                'slug' => 'install',
                'date' => '2026-10-12',
                'title_zh' => '三种安装：Docker、命令行、网页向导',
                'title_en' => 'Three installs: Docker, command line, wizard',
                'excerpt_zh' => '在能看到 artisan 的目录里选一种。网站根目录指向 public。',
                'excerpt_en' => 'Pick one method in the folder that contains artisan. The web root is public.',
                'body_zh' => '<p>三种方式都写在安装页里。</p><ul><li>Docker：<code>docker compose up --build</code></li><li>命令行：<code>composer install</code> 之后执行 <code>php artisan cms:install</code></li><li>网页：启动后打开 <code>/install</code></li></ul><p>装好就能进前台和后台。重新安装前先删掉安装标记。</p>',
                'body_en' => '<p>All three are on the install page.</p><ul><li>Docker: <code>docker compose up --build</code></li><li>Command line: <code>composer install</code>, then <code>php artisan cms:install</code></li><li>Wizard: start the app and open <code>/install</code></li></ul><p>Then the site and the admin are available. Delete the install lock before running the wizard again.</p>',
            ],
            [
                'slug' => 'telegram',
                'date' => '2026-10-12',
                'title_zh' => '飞机群和飞机号',
                'title_en' => 'Telegram group and DM',
                'excerpt_zh' => '群是 t.me/applecms，飞机号是 @sanpang。',
                'excerpt_en' => 'The group is t.me/applecms. DM is @sanpang.',
                'body_zh' => '<p>飞机群：<a href="https://t.me/applecms" target="_blank" rel="noopener">t.me/applecms</a>。</p><p>飞机号：<a href="https://t.me/sanpang" target="_blank" rel="noopener">@sanpang</a>。</p><p>安装和主题问题可以进群问。</p>',
                'body_en' => '<p>Group: <a href="https://t.me/applecms" target="_blank" rel="noopener">t.me/applecms</a>.</p><p>DM: <a href="https://t.me/sanpang" target="_blank" rel="noopener">@sanpang</a>.</p><p>Ask install and theme questions in the group.</p>',
            ],
        ];
    }

    /** @return array{slug: string, date: string, title_zh: string, title_en: string, excerpt_zh: string, excerpt_en: string, body_zh: string, body_en: string}|null */
    public static function find(string $slug): ?array
    {
        foreach (self::all() as $post) {
            if ($post['slug'] === $slug) {
                return $post;
            }
        }

        return null;
    }
}
