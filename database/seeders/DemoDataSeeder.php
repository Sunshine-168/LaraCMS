<?php

namespace Database\Seeders;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\AdminLog;
use App\Models\Cms\Category;
use App\Models\Cms\CmsForm;
use App\Models\Cms\CmsLocale;
use App\Models\Cms\Collector;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\Comment;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\EntryRevision;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Media;
use App\Models\Cms\Member;
use App\Models\Cms\MemberFavorite;
use App\Models\Cms\Redirect;
use App\Models\Cms\StatHit;
use App\Support\CurrentSite;
use Illuminate\Database\Seeder;

/** 安装演示站用：把各业务表填上可点的样例。测试只跑 CmsSeeder，不会走到这里。 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $site = CurrentSite::model() ?? \App\Models\Cms\Site::default();
        if (! $site) {
            return;
        }
        CurrentSite::set($site);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();
        $this->seedOperator($site);
        $member = Member::query()->where('email', 'member@example.com')->first();
        $article = ContentModel::query()->where('slug', 'article')->first();
        $news = Category::query()->get()->first(fn (Category $c) => $c->t('slug') === 'news');
        $launch = Entry::query()->find(2);
        $form = CmsForm::query()->where('slug', 'contact')->first();
        $collector = Collector::query()->where('name', 'Laravel Blog RSS')->first();

        $this->seedDrafts($article, $news, $member);
        $this->seedComments($launch, $member, $admin);
        $this->seedInbox($form, $member);
        $this->seedRedirects();
        $this->seedLogs($admin);
        $this->seedMedia();
        $this->seedCollectorLogs($collector);
        $this->seedHtmlCollector($article, $news);
        $this->seedLocale();
        $this->seedRevision($launch, $admin);
        $this->seedFavorite($member, $launch);
        $this->seedStats();
        $this->touchAdminLogin($admin);
    }

    private function seedOperator(\App\Models\Cms\Site $site): void
    {
        $operatorRoleId = AdminRole::query()->where('slug', 'operator')->value('id');
        $operator = AdminUser::query()->updateOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => '运营同事',
                'password' => 'Operator@12345',
                'role_id' => $operatorRoleId,
                'is_active' => true,
            ]
        );
        $operator->sites()->sync([$site->id]);
    }

    private function seedDrafts(?ContentModel $article, ?Category $news, ?Member $member): void
    {
        if (! $article || ! $news) {
            return;
        }
        Entry::query()->updateOrCreate(
            ['source_guid' => 'demo:pending-review'],
            [
                'category_id' => $news->id,
                'model_id' => $article->id,
                'title' => ['zh_cn' => '待审：会员来稿', 'en' => 'Pending member draft'],
                'slug' => ['zh_cn' => 'pending-review', 'en' => 'pending-review'],
                'excerpt' => ['zh_cn' => '演示待审状态。', 'en' => 'Pending sample.'],
                'content' => ['zh_cn' => '这篇还在待审，仪表盘待办里能看到。', 'en' => 'Waiting for review.'],
                'status' => 'pending',
                'member_id' => $member?->id,
                'published_at' => null,
            ]
        );
        Entry::query()->updateOrCreate(
            ['source_guid' => 'demo:scheduled-news'],
            [
                'category_id' => $news->id,
                'model_id' => $article->id,
                'title' => ['zh_cn' => '定时：下周发布', 'en' => 'Scheduled next week'],
                'slug' => ['zh_cn' => 'scheduled-news', 'en' => 'scheduled-news'],
                'content' => ['zh_cn' => '未到点前台 404。', 'en' => '404 until publish time.'],
                'status' => 'scheduled',
                'published_at' => now()->addWeek(),
            ]
        );
        Entry::query()->updateOrCreate(
            ['source_guid' => 'demo:draft-notes'],
            [
                'category_id' => $news->id,
                'model_id' => $article->id,
                'title' => ['zh_cn' => '草稿：还没写完', 'en' => 'Unfinished draft'],
                'slug' => ['zh_cn' => 'draft-notes', 'en' => 'draft-notes'],
                'content' => ['zh_cn' => '草稿不会出现在前台。', 'en' => 'Draft is not public.'],
                'status' => 'draft',
                'preview_token' => 'demo-preview-token',
                'preview_expires_at' => now()->addDays(7),
                'published_at' => null,
            ]
        );
    }

    private function seedComments(?Entry $entry, ?Member $member, ?AdminUser $admin): void
    {
        if (! $entry) {
            return;
        }
        $approved = Comment::query()->updateOrCreate(
            ['entry_id' => $entry->id, 'author_name' => '读者阿花', 'content' => '写得很清楚，已收藏。'],
            [
                'member_id' => $member?->id,
                'author_email' => 'hua@example.com',
                'status' => Comment::STATUS_APPROVED,
                'moderated_by' => $admin?->id,
                'moderated_at' => now()->subHour(),
                'ip' => '10.0.0.8',
            ]
        );
        Comment::query()->updateOrCreate(
            ['entry_id' => $entry->id, 'parent_id' => $approved->id, 'author_name' => '演示会员', 'content' => '谢谢，下一篇会写安装。'],
            [
                'member_id' => $member?->id,
                'status' => Comment::STATUS_APPROVED,
                'ip' => '10.0.0.9',
            ]
        );
        Comment::query()->updateOrCreate(
            ['entry_id' => $entry->id, 'author_name' => '路过的访客', 'content' => '能加一节备份说明吗？'],
            [
                'author_email' => 'guest@example.com',
                'status' => Comment::STATUS_PENDING,
                'ip' => '10.0.0.10',
            ]
        );
    }

    private function seedInbox(?CmsForm $form, ?Member $member): void
    {
        if (! $form) {
            return;
        }
        $this->upsertInbox($form, $member, [
            'email' => 'biz@example.com',
            'name' => '王经理',
            'message' => '想咨询企业站部署。',
            'ip' => '10.1.1.4',
            'is_read' => false,
            'admin_note' => null,
            'replied_at' => null,
        ]);
        $this->upsertInbox($form, $member, [
            'email' => 'member@example.com',
            'name' => '演示会员',
            'message' => '会员中心投稿入口在哪？',
            'ip' => '10.1.1.5',
            'is_read' => true,
            'admin_note' => '已在邮件里回复路径。',
            'replied_at' => now()->subDay(),
            'member' => true,
        ]);
    }

    /** @param  array{email: string, name: string, message: string, ip: string, is_read: bool, admin_note: ?string, replied_at: mixed, member?: bool}  $row */
    private function upsertInbox(CmsForm $form, ?Member $member, array $row): void
    {
        $found = FormSubmission::query()->where('form_id', $form->id)->get()
            ->first(fn (FormSubmission $s) => ($s->data['email'] ?? null) === $row['email']);
        $payload = [
            'form_id' => $form->id,
            'member_id' => ! empty($row['member']) ? $member?->id : null,
            'data' => ['name' => $row['name'], 'email' => $row['email'], 'message' => $row['message']],
            'ip' => $row['ip'],
            'is_read' => $row['is_read'],
            'admin_note' => $row['admin_note'],
            'replied_at' => $row['replied_at'],
        ];
        if ($found) {
            $found->fill($payload)->save();

            return;
        }
        FormSubmission::query()->create($payload);
    }

    private function seedRedirects(): void
    {
        Redirect::query()->updateOrCreate(
            ['site_id' => CurrentSite::id(), 'source_path' => '/old-about'],
            [
                'target_url' => '/about',
                'status_code' => 301,
                'is_active' => true,
                'note' => '旧关于页',
                'hits' => 4,
                'last_hit_at' => now()->subHours(6),
            ]
        );
        Redirect::query()->updateOrCreate(
            ['site_id' => CurrentSite::id(), 'source_path' => '/temp-promo'],
            [
                'target_url' => '/news',
                'status_code' => 302,
                'is_active' => false,
                'note' => '活动结束已停用',
                'hits' => 0,
            ]
        );
    }

    private function seedLogs(?AdminUser $admin): void
    {
        if (! $admin) {
            return;
        }
        foreach ([
            ['method' => 'POST', 'path' => '/admin/tags', 'detail' => 'Laravel', 'status' => 302],
            ['method' => 'PUT', 'path' => '/admin/settings', 'detail' => '综合 CMS 演示', 'status' => 302],
            ['method' => 'POST', 'path' => '/admin/entries/batch', 'detail' => '选了 2 条', 'status' => 500],
        ] as $row) {
            AdminLog::query()->updateOrCreate(
                [
                    'admin_id' => $admin->id,
                    'path' => $row['path'],
                    'detail' => $row['detail'],
                ],
                [
                    'admin_name' => $admin->name,
                    'method' => $row['method'],
                    'ip' => '127.0.0.1',
                    'status' => $row['status'],
                ]
            );
        }
    }

    private function seedMedia(): void
    {
        Media::query()->updateOrCreate(
            ['site_id' => CurrentSite::id(), 'path' => 'demo/banner.jpg'],
            [
                'disk' => 'public',
                'filename' => 'banner.jpg',
                'mime' => 'image/jpeg',
                'size' => 128000,
                'alt' => '首页轮播示意',
                'collection' => 'banner',
            ]
        );
        Media::query()->updateOrCreate(
            ['site_id' => CurrentSite::id(), 'path' => 'demo/intro.pdf'],
            [
                'disk' => 'public',
                'filename' => 'intro.pdf',
                'mime' => 'application/pdf',
                'size' => 256000,
                'alt' => '产品介绍',
                'collection' => 'docs',
            ]
        );
    }

    private function seedCollectorLogs(?Collector $collector): void
    {
        if (! $collector) {
            return;
        }
        CollectorLog::query()->updateOrCreate(
            ['collector_id' => $collector->id, 'message' => '演示：上次拉到 5 条，新建 1 篇。'],
            ['status' => 'ok', 'fetched' => 5, 'created' => 1, 'skipped' => 4]
        );
        CollectorLog::query()->updateOrCreate(
            ['collector_id' => $collector->id, 'message' => '演示：源站超时。'],
            ['status' => 'fail', 'fetched' => 0, 'created' => 0, 'skipped' => 0]
        );
        $collector->forceFill([
            'last_run_at' => now()->subHours(3),
            'last_status' => 'ok',
            'last_message' => '演示：上次拉到 5 条，新建 1 篇。',
        ])->save();
    }

    private function seedHtmlCollector(?ContentModel $article, ?Category $news): void
    {
        if (! $article || ! $news) {
            return;
        }
        Collector::query()->updateOrCreate(
            ['site_id' => CurrentSite::id(), 'name' => '演示网站列表'],
            [
                'type' => 'html',
                'source_url' => 'https://example.com/news',
                'model_id' => $article->id,
                'category_id' => $news->id,
                'interval_minutes' => 240,
                'limit_items' => 8,
                'is_active' => false,
                'publish_immediately' => false,
                'options' => [
                    'link_selector' => 'h2 a',
                    'title_selector' => 'h2 a',
                    'content_selector' => 'article',
                ],
            ]
        );
    }

    private function seedLocale(): void
    {
        CmsLocale::query()->updateOrCreate(
            ['site_id' => CurrentSite::id(), 'code' => 'ja'],
            [
                'label' => '日本語',
                'native' => '日本語',
                'is_default' => false,
                'hide_default' => true,
                'is_active' => false,
                'sort' => 20,
            ]
        );
    }

    private function seedRevision(?Entry $entry, ?AdminUser $admin): void
    {
        if (! $entry) {
            return;
        }
        EntryRevision::query()->updateOrCreate(
            ['entry_id' => $entry->id, 'title_hint' => '综合 CMS 上线（初稿）'],
            [
                'admin_id' => $admin?->id,
                'snapshot' => [
                    'title' => $entry->title,
                    'content' => ['zh_cn' => '更早一版正文。', 'en' => 'Earlier body.'],
                    'status' => 'published',
                ],
            ]
        );
    }

    private function seedFavorite(?Member $member, ?Entry $entry): void
    {
        if (! $member || ! $entry) {
            return;
        }
        MemberFavorite::query()->updateOrCreate(
            ['member_id' => $member->id, 'entry_id' => $entry->id],
            []
        );
    }

    private function seedStats(): void
    {
        $ua = 'Mozilla/5.0 Chrome/120.0.0.0';
        $rows = [
            ['path' => '/', 'hash' => 'demo-home-today', 'spider' => false, 'at' => now()],
            ['path' => '/news', 'hash' => 'demo-news-today', 'spider' => false, 'at' => now()->subHours(2)],
            ['path' => '/', 'hash' => 'demo-home-yday', 'spider' => false, 'at' => now()->subDay()],
            ['path' => '/about', 'hash' => 'demo-baidu', 'spider' => true, 'name' => 'Baiduspider', 'at' => now()->subHours(1)],
            ['path' => '/missing', 'hash' => 'demo-404', 'spider' => false, 'status' => 404, 'at' => now()->subMinutes(20)],
        ];
        foreach ($rows as $row) {
            StatHit::query()->updateOrCreate(
                ['visitor_hash' => hash('sha256', $row['hash']), 'path' => $row['path']],
                [
                    'ip' => '203.0.113.'.strlen($row['hash']),
                    'user_agent' => $ua,
                    'referer' => $row['path'] === '/' ? 'https://www.google.com/' : null,
                    'locale' => 'zh_cn',
                    'is_spider' => $row['spider'],
                    'spider_name' => $row['name'] ?? null,
                    'status_code' => $row['status'] ?? 200,
                    'created_at' => $row['at'],
                ]
            );
        }
    }

    private function touchAdminLogin(?AdminUser $admin): void
    {
        if (! $admin) {
            return;
        }
        $admin->forceFill([
            'last_login_at' => now()->subHours(5),
            'last_login_ip' => '127.0.0.1',
        ])->save();
    }
}
