<?php

namespace Database\Seeders;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\CmsForm;
use App\Models\Cms\Collector;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\ModelField;
use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Models\Cms\Setting;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\Tag;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminRbacSeeder::class);

        $superRoleId = AdminRole::query()->where('slug', 'super_admin')->value('id');
        $editorRoleId = AdminRole::query()->where('slug', 'editor')->value('id');

        AdminUser::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '超级管理员',
                'password' => 'Admin@12345',
                'role_id' => $superRoleId,
                'is_active' => true,
            ]
        );
        AdminUser::query()->updateOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => '编辑',
                'password' => 'Editor@12345',
                'role_id' => $editorRoleId,
                'is_active' => true,
            ]
        );
        $defaultGroup = MemberGroup::query()->updateOrCreate(
            ['slug' => 'default'],
            [
                'name' => '普通会员',
                'sort' => 0,
                'is_active' => true,
                'permissions' => MemberGroup::permissionKeys(),
            ]
        );
        Member::query()->updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => '演示会员',
                'password' => 'Member@12345',
                'is_active' => true,
                'group_id' => $defaultGroup->id,
            ]
        );

        Setting::setValue('site_name', '综合 CMS 演示');
        Setting::setValue('site_description', '企业站/下载站/导航/源码/博客/漫画/体育聚合');
        Setting::setValue('site_keywords', 'CMS,Laravel,博客,漫画');
        Setting::setValue('theme', 'enterprise');
        Setting::setValue('mobile_theme', 'mobile');
        Setting::setValue('mobile_detect', 'ua');
        Setting::setValue('url_mode', 'cat_entry');

        $models = $this->seedModels();
        $cats = $this->seedCategories($models);
        $this->seedEntries($models, $cats);
        $this->seedCollectors($models, $cats);
        $this->seedRecommends($models, $cats);
        $this->seedForms();
        $this->seedTags($models);

        if (! is_file(storage_path('app/install.lock'))) {
            file_put_contents(storage_path('app/install.lock'), "installed_at=".now()->toDateTimeString()."\nseed_demo=1\n");
        }
    }

    /** 示例：公开 RSS 采集进博客栏目（默认停用，避免 seed 时外网请求） */
    private function seedCollectors(array $models, array $cats): void
    {
        Collector::query()->updateOrCreate(
            ['name' => 'Laravel Blog RSS'],
            [
                'type' => 'rss',
                'source_url' => 'https://blog.laravel.com/feed',
                'model_id' => $models['article']->id,
                'category_id' => $cats['blog']->id,
                'interval_minutes' => 120,
                'limit_items' => 5,
                'is_active' => false,
                'publish_immediately' => true,
                'options' => null,
            ]
        );
    }

    /** @return array<string, ContentModel> */
    private function seedModels(): array
    {
        $defs = [
            'article' => ['name' => '文章', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show', 'fields' => []],
            'page' => ['name' => '单页', 'kind' => 'single', 'list_tpl' => null, 'show_tpl' => 'show.show', 'fields' => []],
            'product' => [
                'name' => '产品', 'kind' => 'list', 'list_tpl' => 'list.list_product', 'show_tpl' => 'show.show_product',
                'fields' => [
                    ['name' => 'price', 'label' => '价格', 'type' => 'text', 'is_list' => true],
                    ['name' => 'buylink', 'label' => '购买链接', 'type' => 'url'],
                ],
            ],
            'job' => [
                'name' => '招聘', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'salary', 'label' => '薪资', 'type' => 'text', 'is_list' => true],
                    ['name' => 'city', 'label' => '城市', 'type' => 'text', 'is_filter' => true, 'options' => ['choices' => ['北京', '上海', '远程']]],
                ],
            ],
            'download' => [
                'name' => '下载', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'version', 'label' => '版本', 'type' => 'text', 'is_list' => true],
                    ['name' => 'size', 'label' => '大小', 'type' => 'text'],
                    ['name' => 'platform', 'label' => '平台', 'type' => 'select', 'is_filter' => true, 'is_list' => true, 'options' => ['choices' => ['windows', 'mac', 'linux', 'android']]],
                    ['name' => 'file', 'label' => '文件路径/URL', 'type' => 'file'],
                ],
            ],
            'link' => [
                'name' => '导航链接', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'url', 'label' => '网址', 'type' => 'url', 'is_required' => true],
                    ['name' => 'icon', 'label' => '图标', 'type' => 'image'],
                ],
            ],
            'source' => [
                'name' => '源码', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'demo_url', 'label' => '演示地址', 'type' => 'url'],
                    ['name' => 'license', 'label' => '授权', 'type' => 'select', 'is_filter' => true, 'options' => ['choices' => ['MIT', 'GPL', '商业']]],
                    ['name' => 'price', 'label' => '价格', 'type' => 'text', 'is_list' => true],
                    ['name' => 'package', 'label' => '源码包路径/URL', 'type' => 'file'],
                ],
            ],
            'manga' => [
                'name' => '漫画', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'author', 'label' => '作者', 'type' => 'text', 'is_list' => true],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'is_filter' => true, 'is_list' => true, 'options' => ['choices' => ['连载', '完结']]],
                    ['name' => 'area', 'label' => '地区', 'type' => 'select', 'is_filter' => true, 'options' => ['choices' => ['国漫', '日漫', '韩漫']]],
                ],
            ],
            'chapter' => [
                'name' => '章节', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.chapter',
                'fields' => [
                    ['name' => 'manga_id', 'label' => '所属漫画 ID', 'type' => 'number', 'is_required' => true],
                    ['name' => 'pages', 'label' => '图片URL（每行一张）', 'type' => 'textarea'],
                ],
            ],
            'signal' => [
                'name' => '体育信号', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'sport', 'label' => '项目', 'type' => 'select', 'is_filter' => true, 'is_list' => true, 'options' => ['choices' => ['足球', '篮球', '网球', '综合']]],
                    ['name' => 'kickoff', 'label' => '开赛时间', 'type' => 'text', 'is_list' => true],
                    ['name' => 'source_url', 'label' => '信号/资讯链接', 'type' => 'url', 'is_required' => true],
                    ['name' => 'quality', 'label' => '清晰度', 'type' => 'select', 'options' => ['choices' => ['高清', '标清', '文字']]],
                ],
            ],
        ];

        $out = [];
        $sort = 0;
        foreach ($defs as $slug => $def) {
            $model = ContentModel::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $def['name'],
                    'kind' => $def['kind'],
                    'list_tpl' => $def['list_tpl'],
                    'show_tpl' => $def['show_tpl'],
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );
            ModelField::query()->where('model_id', $model->id)->delete();
            foreach ($def['fields'] as $i => $field) {
                ModelField::query()->create([
                    'model_id' => $model->id,
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'options' => $field['options'] ?? null,
                    'is_required' => $field['is_required'] ?? false,
                    'is_filter' => $field['is_filter'] ?? false,
                    'is_list' => $field['is_list'] ?? false,
                    'sort_order' => $i,
                ]);
            }
            $out[$slug] = $model;
        }

        return $out;
    }

    /** @param  array<string, ContentModel>  $models */
    private function seedCategories(array $models): array
    {
        $specs = [
            'news' => ['title' => ['zh_cn' => '新闻中心', 'en' => 'News'], 'type' => 'list', 'model' => 'article', 'menu' => true],
            'about' => ['title' => ['zh_cn' => '关于我们', 'en' => 'About'], 'type' => 'single', 'model' => 'page', 'menu' => true],
            'products' => ['title' => ['zh_cn' => '产品中心', 'en' => 'Products'], 'type' => 'list', 'model' => 'product', 'menu' => true, 'list_tpl' => 'list.list_product', 'show_tpl' => 'show.show_product'],
            'jobs' => ['title' => ['zh_cn' => '加入我们', 'en' => 'Jobs'], 'type' => 'list', 'model' => 'job', 'menu' => true],
            'downloads' => ['title' => ['zh_cn' => '下载中心', 'en' => 'Downloads'], 'type' => 'list', 'model' => 'download', 'menu' => true],
            'nav-tools' => ['title' => ['zh_cn' => '常用工具', 'en' => 'Tools'], 'type' => 'list', 'model' => 'link', 'menu' => true],
            'nav-dev' => ['title' => ['zh_cn' => '开发资源', 'en' => 'Dev'], 'type' => 'list', 'model' => 'link', 'menu' => true],
            'sources' => ['title' => ['zh_cn' => '源码市场', 'en' => 'Source'], 'type' => 'list', 'model' => 'source', 'menu' => true],
            'blog' => ['title' => ['zh_cn' => '博客', 'en' => 'Blog'], 'type' => 'list', 'model' => 'article', 'menu' => true],
            'comics' => ['title' => ['zh_cn' => '漫画库', 'en' => 'Comics'], 'type' => 'list', 'model' => 'manga', 'menu' => true],
            'chapters' => ['title' => ['zh_cn' => '章节', 'en' => 'Chapters'], 'type' => 'list', 'model' => 'chapter', 'menu' => false, 'show_tpl' => 'show.chapter'],
            'matches' => ['title' => ['zh_cn' => '赛事信号', 'en' => 'Matches'], 'type' => 'list', 'model' => 'signal', 'menu' => true],
        ];

        $cats = [];
        $i = 0;
        foreach ($specs as $slug => $spec) {
            $cats[$slug] = Category::query()->updateOrCreate(
                ['id' => $i + 1],
                [
                    'title' => $spec['title'],
                    'slug' => ['zh_cn' => $slug, 'en' => $slug],
                    'type' => $spec['type'],
                    'model_id' => $models[$spec['model']]->id,
                    'list_tpl' => $spec['list_tpl'] ?? null,
                    'show_tpl' => $spec['show_tpl'] ?? null,
                    'is_menu' => $spec['menu'],
                    'page_size' => 10,
                    'sort_order' => $i * 10,
                    'status' => 'published',
                ]
            );
            $i++;
        }

        return $cats;
    }

    private function seedEntries(array $models, array $cats): void
    {
        Entry::query()->updateOrCreate(['id' => 1], [
            'category_id' => $cats['about']->id,
            'model_id' => $models['page']->id,
            'title' => ['zh_cn' => '关于我们', 'en' => 'About Us'],
            'slug' => ['zh_cn' => 'about', 'en' => 'about'],
            'content' => [
                'zh_cn' => "我们是一家提供综合 CMS 解决方案的团队。\n支持企业站、下载站、导航站与源码站。",
                'en' => "We build a flexible CMS for enterprise, download, navigation and source sites.",
            ],
            'flags' => 'about',
            'status' => 'published',
            'is_homepage' => false,
            'published_at' => now(),
            'sort_order' => 0,
        ]);

        Entry::query()->updateOrCreate(['id' => 2], [
            'category_id' => $cats['news']->id,
            'model_id' => $models['article']->id,
            'title' => ['zh_cn' => '综合 CMS 上线', 'en' => 'CMS Launch'],
            'slug' => ['zh_cn' => 'cms-launch', 'en' => 'cms-launch'],
            'excerpt' => ['zh_cn' => '栏目+模型+Blade 标签驱动。', 'en' => 'Channel + models + Blade tags.'],
            'content' => ['zh_cn' => "欢迎使用综合 CMS。\n主题可用 @cmsContent 拉取内容。", 'en' => "Welcome.\nThemes use @cmsContent."],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 3], [
            'category_id' => $cats['products']->id,
            'model_id' => $models['product']->id,
            'title' => ['zh_cn' => '智能网关', 'en' => 'Smart Gateway'],
            'slug' => ['zh_cn' => 'smart-gateway', 'en' => 'smart-gateway'],
            'content' => ['zh_cn' => '企业级智能网关产品介绍。', 'en' => 'Enterprise gateway product.'],
            'cover' => 'https://picsum.photos/seed/gw/640/360',
            'attrs' => ['price' => '¥9,999', 'buylink' => '/about'],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 4], [
            'category_id' => $cats['downloads']->id,
            'model_id' => $models['download']->id,
            'title' => ['zh_cn' => '客户端 Windows 版', 'en' => 'Client for Windows'],
            'slug' => ['zh_cn' => 'client-windows', 'en' => 'client-windows'],
            'content' => ['zh_cn' => '官方 Windows 安装包。', 'en' => 'Official Windows package.'],
            'attrs' => [
                'version' => '1.0.0',
                'size' => '48 MB',
                'platform' => 'windows',
                'file' => 'https://example.com/files/client-win.exe',
                'require_login' => true,
            ],
            'status' => 'published',
            'published_at' => now(),
            'downloads_count' => 12,
        ]);

        Entry::query()->updateOrCreate(['id' => 5], [
            'category_id' => $cats['nav-tools']->id,
            'model_id' => $models['link']->id,
            'title' => ['zh_cn' => 'GitHub', 'en' => 'GitHub'],
            'slug' => ['zh_cn' => 'github', 'en' => 'github'],
            'attrs' => ['url' => 'https://github.com', 'icon' => 'https://picsum.photos/seed/gh/80'],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 6], [
            'category_id' => $cats['nav-dev']->id,
            'model_id' => $models['link']->id,
            'title' => ['zh_cn' => 'Laravel', 'en' => 'Laravel'],
            'slug' => ['zh_cn' => 'laravel', 'en' => 'laravel'],
            'attrs' => ['url' => 'https://laravel.com', 'icon' => 'https://picsum.photos/seed/lv/80'],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 7], [
            'category_id' => $cats['sources']->id,
            'model_id' => $models['source']->id,
            'title' => ['zh_cn' => '商城源码 Pro', 'en' => 'Shop Source Pro'],
            'slug' => ['zh_cn' => 'shop-pro', 'en' => 'shop-pro'],
            'content' => ['zh_cn' => '完整商城前后端源码包。', 'en' => 'Full shop source package.'],
            'attrs' => [
                'demo_url' => 'https://example.com/demo',
                'license' => '商业',
                'price' => '¥199',
                'package' => 'https://example.com/files/shop-pro.zip',
            ],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 8], [
            'category_id' => $cats['jobs']->id,
            'model_id' => $models['job']->id,
            'title' => ['zh_cn' => 'PHP 工程师', 'en' => 'PHP Engineer'],
            'slug' => ['zh_cn' => 'php-engineer', 'en' => 'php-engineer'],
            'content' => ['zh_cn' => '熟悉 Laravel，热爱开源。', 'en' => 'Laravel experience required.'],
            'attrs' => ['salary' => '20-35K', 'city' => '远程'],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 9], [
            'category_id' => $cats['blog']->id,
            'model_id' => $models['article']->id,
            'title' => ['zh_cn' => '写给站长的第一篇博客', 'en' => 'First blog post'],
            'slug' => ['zh_cn' => 'hello-blog', 'en' => 'hello-blog'],
            'excerpt' => ['zh_cn' => '用 blog 主题也能跑简单博客。', 'en' => 'Simple blogging with the blog theme.'],
            'content' => ['zh_cn' => "这是博客示例正文。\n切换主题为 blog 即可。", 'en' => "Sample blog body.\nSwitch theme to blog."],
            'status' => 'published',
            'published_at' => now(),
            'flags' => 'recommend',
        ]);

        Entry::query()->updateOrCreate(['id' => 10], [
            'category_id' => $cats['comics']->id,
            'model_id' => $models['manga']->id,
            'title' => ['zh_cn' => '星轨旅人', 'en' => 'Star Traveler'],
            'slug' => ['zh_cn' => 'star-traveler', 'en' => 'star-traveler'],
            'content' => ['zh_cn' => '一部连载示例漫画。', 'en' => 'Sample ongoing comic.'],
            'cover' => 'https://picsum.photos/seed/manga/400/560',
            'attrs' => ['author' => '示例作者', 'status' => '连载', 'area' => '国漫'],
            'status' => 'published',
            'published_at' => now(),
        ]);

        Entry::query()->updateOrCreate(['id' => 11], [
            'category_id' => $cats['chapters']->id,
            'model_id' => $models['chapter']->id,
            'title' => ['zh_cn' => '第 1 话', 'en' => 'Chapter 1'],
            'slug' => ['zh_cn' => 'star-ch1', 'en' => 'star-ch1'],
            'content' => ['zh_cn' => '', 'en' => ''],
            'attrs' => [
                'manga_id' => 10,
                'pages' => "https://picsum.photos/seed/p1/800/1200\nhttps://picsum.photos/seed/p2/800/1200\nhttps://picsum.photos/seed/p3/800/1200",
            ],
            'status' => 'published',
            'published_at' => now(),
            'sort_order' => 1,
        ]);

        Entry::query()->updateOrCreate(['id' => 12], [
            'category_id' => $cats['matches']->id,
            'model_id' => $models['signal']->id,
            'title' => ['zh_cn' => '示例德比 · 资讯源', 'en' => 'Sample Derby'],
            'slug' => ['zh_cn' => 'sample-derby', 'en' => 'sample-derby'],
            'content' => ['zh_cn' => '合法体育资讯/公开信号源目录示例。', 'en' => 'Sample sports info directory.'],
            'attrs' => [
                'sport' => '足球',
                'kickoff' => '2026-08-03 20:00',
                'source_url' => 'https://www.fifa.com',
                'quality' => '高清',
            ],
            'status' => 'published',
            'published_at' => now(),
            'flags' => 'live',
        ]);
    }

    private function seedRecommends(array $models = [], array $cats = []): void
    {
        $banner = Recommend::query()->updateOrCreate(
            ['name' => 'home_banner'],
            ['title' => '首页轮播', 'type' => 'image', 'is_active' => true]
        );
        RecommendItem::query()->where('recommend_id', $banner->id)->delete();
        RecommendItem::query()->create([
            'recommend_id' => $banner->id,
            'title' => ['zh_cn' => '打造专业企业官网', 'en' => 'Build your enterprise site'],
            'notes' => ['zh_cn' => '栏目 · 模型 · 模板标签', 'en' => 'Channels · Models · Tags'],
            'image' => 'https://picsum.photos/seed/banner/1200/400',
            'url' => '/about',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $partners = Recommend::query()->updateOrCreate(
            ['name' => 'partners'],
            ['title' => '合作伙伴', 'type' => 'link', 'is_active' => true]
        );
        RecommendItem::query()->where('recommend_id', $partners->id)->delete();
        foreach ([['阿里云', 'https://www.aliyun.com'], ['腾讯云', 'https://cloud.tencent.com']] as $i => [$title, $url]) {
            RecommendItem::query()->create([
                'recommend_id' => $partners->id,
                'title' => ['zh_cn' => $title, 'en' => $title],
                'url' => $url,
                'image' => 'https://picsum.photos/seed/p'.$i.'/120/40',
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        $hot = Recommend::query()->updateOrCreate(
            ['name' => 'hot_links'],
            ['title' => '热门导航', 'type' => 'link', 'is_active' => true]
        );
        RecommendItem::query()->where('recommend_id', $hot->id)->delete();
        RecommendItem::query()->create([
            'recommend_id' => $hot->id,
            'title' => ['zh_cn' => '百度', 'en' => 'Baidu'],
            'url' => 'https://www.baidu.com',
            'image' => 'https://picsum.photos/seed/bd/80',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $live = Recommend::query()->updateOrCreate(
            ['name' => 'live_board'],
            ['title' => '今日焦点', 'type' => 'link', 'is_active' => true]
        );
        RecommendItem::query()->where('recommend_id', $live->id)->delete();
        RecommendItem::query()->create([
            'recommend_id' => $live->id,
            'title' => ['zh_cn' => '今日焦点赛事', 'en' => 'Today focus'],
            'notes' => ['zh_cn' => '体育聚合首页推荐', 'en' => 'Sports board'],
            'url' => '/matches',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Recommend::query()->updateOrCreate(
            ['name' => 'latest_news'],
            [
                'title' => '最新新闻（查询型）',
                'type' => 'query',
                'is_active' => true,
                'query_params' => [
                    'model_id' => $models['article']->id ?? null,
                    'category_ids' => isset($cats['news']) ? [$cats['news']->id] : [],
                    'flag' => null,
                    'limit' => 6,
                    'order' => 'published_at_desc',
                ],
            ]
        );
    }

    private function seedForms(): void
    {
        CmsForm::query()->updateOrCreate(
            ['slug' => 'contact'],
            [
                'name' => '联系我们',
                'fields' => [
                    ['name' => 'name', 'label' => '姓名', 'type' => 'text'],
                    ['name' => 'email', 'label' => '邮箱', 'type' => 'email'],
                    ['name' => 'message', 'label' => '留言', 'type' => 'textarea'],
                ],
                'is_active' => true,
            ]
        );
    }

    private function seedTags(array $models): void
    {
        $tag = Tag::query()->updateOrCreate(
            ['id' => 1],
            [
                'title' => ['zh_cn' => 'Laravel', 'en' => 'Laravel'],
                'slug' => ['zh_cn' => 'laravel', 'en' => 'laravel'],
            ]
        );
        if ($entry = Entry::query()->find(7)) {
            $entry->tags()->sync([$tag->id]);
        }
    }
}
