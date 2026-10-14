<?php

namespace Database\Seeders;

use App\Models\Cms\Category;
use App\Models\Cms\CmsForm;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Menu;
use App\Models\Cms\MenuItem;
use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Services\Cms\ThemePackInstaller;
use App\Support\CurrentSite;
use Illuminate\Database\Seeder;

/** 包网官网占位站。不改默认站。重复执行会更新文案，不另开站点。 */
class BcSiteSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::query()->where('domain', 'bc.localhost')->first();
        if (! $site) {
            $site = new Site;
            $site->name = 'IG包网';
            $site->domain = 'bc.localhost';
            $site->is_active = true;
            $site->is_default = false;
            $site->theme = 'bc';
            $site->remark = '包网官网';
            $site->save();
            $source = Site::query()->where('is_default', true)->where('id', '!=', $site->id)->first();
            if ($source) {
                $site->copyBasicsFrom($source);
            }
        }

        $site->name = 'IG包网';
        $site->theme = 'bc';
        $site->is_active = true;
        $site->is_default = false;
        $site->save();

        CurrentSite::set($site);
        Setting::setValue('theme', 'bc');
        Setting::setValue('site_name', 'IG包网');
        Setting::setValue('site_keywords', 'IG包网,包网,游戏API');
        Setting::setValue('site_description', 'IG包网。标准包网、定制包网和游戏 API。');
        Setting::setValue('footer_text', '');
        Setting::setValue('url_mode', 'cat_entry');
        Setting::setValue('mobile_theme', '');
        Setting::setValue('primary_color', '');
        Setting::setValue('hero_title', '');
        Setting::setValue('hero_subtitle', '');
        Setting::setValue('comments_enabled', '0');

        $pack = app(ThemePackInstaller::class)->ensure('bc');
        if (empty($pack['ok'])) {
            $this->command?->error($pack['msg'] ?? '主题没有装上');

            return;
        }

        $page = ContentModel::query()->where('slug', 'page')->first();
        $product = ContentModel::query()->where('slug', 'product')->first();
        $article = ContentModel::query()->where('slug', 'article')->first();
        if (! $page || ! $product || ! $article) {
            $this->command?->error('包网主题的模型没有建起来');

            return;
        }

        $this->pages($page);
        $this->packages($product);
        $this->insights($article);
        $this->modules();
        $this->markets();
        $this->form();
        $this->menu();

        $this->command?->info('包网站：http://bc.localhost:8000');
    }

    private function pages(ContentModel $page): void
    {
        $this->put($this->category('gw-api'), $page, 'bc:api', [
            'zh_cn' => '游戏 API',
            'en' => 'Game API',
        ], [
            'zh_cn' => 'game-api',
            'en' => 'game-api',
        ], [
            'zh_cn' => '适合已经有前台、只想补游戏的团队。',
            'en' => 'For teams that already have a front and only need games.',
        ], [
            'zh_cn' => '<p>游戏 API 不更换整套前台。对接一次之后，按品类开关游戏。</p><h2>品类</h2><ul><li>真人</li><li>体育</li><li>电子</li><li>棋牌</li><li>彩票</li></ul><h2>还没写死的数字</h2><p>供应商数量、游戏数量、接入周期：待补充。页面上不放还没确认的规模。</p>',
            'en' => '<p>The game API does not replace an existing front. After one connection, games are switched on by category.</p><h2>Categories</h2><ul><li>Live</li><li>Sports</li><li>Slots</li><li>Table games</li><li>Lottery</li></ul><h2>Numbers still open</h2><p>Supplier count, game count, and timing are not filled in yet.</p>',
        ]);

        $this->put($this->category('gw-platform'), $page, 'bc:platform', [
            'zh_cn' => '系统能力',
            'en' => 'Platform',
        ], [
            'zh_cn' => 'platform',
            'en' => 'platform',
        ], [
            'zh_cn' => '后台按运营动作来写，不按技术名词堆。',
            'en' => 'The admin is described by what operators do.',
        ], [
            'zh_cn' => '<p>这些是招商站要讲清的后台能力。具体规则在方案里约定。</p><ul><li>支付：按目标市场接渠道，支持哪些方式待补充。</li><li>代理：层级和佣金可以配置。</li><li>活动：注册、充值、签到这一类活动可以建。</li><li>返水：按约定自动或人工处理，默认写自动，细则待补充。</li><li>风控：异常行为有记录和提醒。</li><li>报表：按时间和代理查看营收。</li><li>语言和货币：一个后台管多种，具体清单待补充。</li></ul>',
            'en' => '<p>These are the admin areas the sales site should explain. The exact rules belong in the agreement.</p><ul><li>Payments follow the market. The channel list is not filled in yet.</li><li>Agents have levels and commission settings.</li><li>Promotions can cover signup, deposit, and check-in.</li><li>Rebates can run on a schedule. The detail is not filled in yet.</li><li>Risk control keeps a record of unusual activity.</li><li>Reports show revenue by time and agent.</li><li>One admin can hold more than one language and currency. The list is not filled in yet.</li></ul>',
        ]);

        $this->put($this->category('gw-markets'), $page, 'bc:markets', [
            'zh_cn' => '目标市场',
            'en' => 'Markets',
        ], [
            'zh_cn' => 'markets',
            'en' => 'markets',
        ], [
            'zh_cn' => '第一版只做区域，不拆成十个国家页。',
            'en' => 'Regions first. Country pages come later.',
        ], [
            'zh_cn' => '<p>语言、货币和支付按市场配置。上线前要确认目标市场允许这种业务，以及需要哪一种许可。</p><h2>东南亚</h2><p>越南、泰国、印尼、菲律宾、柬埔寨、缅甸。当地语言和支付待按国家补。</p><h2>东亚</h2><p>日本、韩国。页面规则和客服语言待补充。</p><h2>南亚</h2><p>印度、孟加拉。当地游戏偏好待补充。</p><h2>拉美</h2><p>巴西为主。货币和支付待补充。</p>',
            'en' => '<p>Language, currency, and payments follow the market. Before launch, confirm the market allows the business and which licence it needs.</p><h2>Southeast Asia</h2><p>Vietnam, Thailand, Indonesia, the Philippines, Cambodia, Myanmar. Local language and payments are filled in per country later.</p><h2>East Asia</h2><p>Japan and Korea. House rules and support language are not filled in yet.</p><h2>South Asia</h2><p>India and Bangladesh. Local game preferences are not filled in yet.</p><h2>Latin America</h2><p>Brazil first. Currency and payments are not filled in yet.</p>',
        ]);

        $this->put($this->category('gw-about'), $page, 'bc:about', [
            'zh_cn' => '关于我们',
            'en' => 'About',
        ], [
            'zh_cn' => 'about',
            'en' => 'about',
        ], [
            'zh_cn' => 'IG包网做方案、接口和后台。',
            'en' => 'IG builds packages, the game API, and the admin.',
        ], [
            'zh_cn' => '<p>IG包网把前台、后台和游戏接入一起交给准备开站的团队。方案分标准、定制和游戏 API 三档。</p><p>公司介绍、办公地点和案例可以在后台这页改。</p>',
            'en' => '<p>IG delivers the front, the admin, and game connections to teams preparing a launch. There are three packages: standard, custom, and game API.</p><p>Company story, office, and cases can be edited on this page.</p>',
        ]);

        $this->put($this->category('gw-faq'), $page, 'bc:faq', [
            'zh_cn' => '常见问题',
            'en' => 'FAQ',
        ], [
            'zh_cn' => 'faq',
            'en' => 'faq',
        ], [
            'zh_cn' => '开站前最常问的几件。',
            'en' => 'Questions that come up before a launch.',
        ], [
            'zh_cn' => '<h2>包网和自己开发差在哪？</h2><p>包网用现成系统上线。自己开发要单独做前台、后台和接入，时间更长。</p><h2>标准和定制差在哪？</h2><p>标准用现有模板，改品牌元素。定制改界面和流程，周期更长。</p><h2>上线要准备什么？</h2><p>品牌名、Logo、对接人，以及目标市场。支付和游戏清单在方案里定。</p><h2>有公开报价吗？</h2><p>没有。按档位和改动范围单独报，页面上不放参考价。</p><h2>可以看演示吗？</h2><p>演示账号待提供。现在先看方案说明。</p><h2>上线后谁维护？</h2><p>按合同里的维护范围。页面不承诺具体响应时间，等写进方案再改这里。</p>',
            'en' => '<h2>How is this different from building it?</h2><p>A package launches on a system that already exists. Building it means a new front, admin, and connections, and takes longer.</p><h2>Standard or custom?</h2><p>Standard keeps the current templates and changes the brand. Custom changes layout and flow, and takes longer.</p><h2>What do we send?</h2><p>Brand name, logo, a contact, and the target market. Payments and the game list are set in the package.</p><h2>Is there a public price?</h2><p>No. Price follows the package and the amount of change.</p><h2>Is there a demo?</h2><p>A demo account is not ready. Read the packages first.</p><h2>Who maintains it?</h2><p>Whatever the agreement says. Response time is not promised on this page.</p>',
        ]);

        $this->put($this->category('gw-contact'), $page, 'bc:contact', [
            'zh_cn' => '联系我们',
            'en' => 'Contact',
        ], [
            'zh_cn' => 'contact',
            'en' => 'contact',
        ], [
            'zh_cn' => '先留 Telegram 和目标市场。',
            'en' => 'Leave Telegram and the target market.',
        ], [
            'zh_cn' => '<p>商务 Telegram 和邮箱在主题设置里填。没填之前，用这张表。提交后在后台「表单」收件箱里看。</p>',
            'en' => '<p>Set Telegram and email in the theme settings. Until then, use this form. Submissions sit in the admin inbox.</p>',
        ]);
    }

    private function packages(ContentModel $product): void
    {
        $cat = $this->category('gw-solutions');
        $rows = [
            [
                'bc:pkg-standard', 30, '标准包网', 'Standard package', 'standard',
                '用现有模板上线，先改品牌名、Logo 和颜色。',
                'Launch on the current templates. Change the name, logo, and colors first.',
                '<p>适合想先开站、以后再改界面的团队。</p><h2>包含</h2><ul><li>电脑和手机前台</li><li>管理后台、代理后台</li><li>基础活动、报表</li><li>按市场接游戏和支付，清单另附</li></ul><h2>不含</h2><p>整套界面重做、单独的游戏规则。这些走定制。</p><h2>周期</h2><p>待补充。只写「短于定制」，不写还没承诺的天数。</p>',
                '<p>For teams that want to launch first and change the layout later.</p><h2>Includes</h2><ul><li>Desktop and mobile front</li><li>Admin and agent back offices</li><li>Basic promotions and reports</li><li>Games and payments for the market, listed separately</li></ul><h2>Not included</h2><p>A new interface, or rules written for one game. Those are custom.</p><h2>Timing</h2><p>Not filled in. It is shorter than custom. No day count until it is agreed.</p>',
            ],
            [
                'bc:pkg-custom', 20, '定制包网', 'Custom package', 'custom',
                '按品牌改界面和流程，适合已经知道要长什么样的团队。',
                'Layout and flow follow the brand. For teams that already know the shape.',
                '<p>在标准方案上改页面结构和操作路径。</p><h2>包含</h2><ul><li>标准方案里的前台和后台</li><li>按确认过的稿改界面</li><li>约定范围内的流程调整</li></ul><h2>周期</h2><p>按改动范围另估，待补充。</p>',
                '<p>Page structure and paths change on top of the standard package.</p><h2>Includes</h2><ul><li>The standard front and admin</li><li>Layout from an approved design</li><li>Flow changes inside the agreed scope</li></ul><h2>Timing</h2><p>Estimated from the scope. Not filled in yet.</p>',
            ],
            [
                'bc:pkg-api', 10, '游戏 API', 'Game API', 'api',
                '不换整套前台，只把游戏接到已有站点。',
                'Keep the current front. Connect games to a site that already exists.',
                '<p>适合已有品牌站、要补真人、体育、电子、棋牌或彩票的团队。</p><h2>包含</h2><ul><li>一次对接</li><li>按品类开关</li><li>接入说明和联调</li></ul><h2>不含</h2><p>新的品牌前台。要整站就选标准或定制。</p>',
                '<p>For teams that already have a site and need live, sports, slots, table games, or lottery.</p><h2>Includes</h2><ul><li>One connection</li><li>Categories that can be switched</li><li>Notes and a joint test</li></ul><h2>Not included</h2><p>A new branded front. For a whole site, choose standard or custom.</p>',
            ],
        ];
        foreach ($rows as [$guid, $sort, $zh, $en, $slug, $zhEx, $enEx, $zhHtml, $enHtml]) {
            $this->put($cat, $product, $guid, [
                'zh_cn' => $zh,
                'en' => $en,
            ], [
                'zh_cn' => $slug,
                'en' => $slug,
            ], [
                'zh_cn' => $zhEx,
                'en' => $enEx,
            ], [
                'zh_cn' => $zhHtml,
                'en' => $enHtml,
            ], $sort);
        }
    }

    private function insights(ContentModel $article): void
    {
        $cat = $this->category('gw-insights');
        $rows = [
            ['bc:post-check', 3, '开站前先确认的五件事', 'Five checks before a launch', 'five-checks', '市场、档位、品牌素材、对接人、许可。', 'Market, package, brand files, a contact, and the licence.'],
            ['bc:post-standard', 2, '什么时候用标准方案', 'When the standard package fits', 'when-standard', '还没有定稿界面、想先上线时用标准方案。', 'Use it when the layout is not final and the launch should come first.'],
            ['bc:post-files', 1, '品牌素材要准备哪些', 'Brand files to prepare', 'brand-files', '名字、Logo、主色，以及一句话介绍。', 'Name, logo, a main color, and one sentence.'],
        ];
        foreach ($rows as [$guid, $sort, $zh, $en, $slug, $zhEx, $enEx]) {
            $this->put($cat, $article, $guid, [
                'zh_cn' => $zh,
                'en' => $en,
            ], [
                'zh_cn' => $slug,
                'en' => $slug,
            ], [
                'zh_cn' => $zhEx,
                'en' => $enEx,
            ], [
                'zh_cn' => '<p>'.$zhEx.'</p><p>这篇是占位。正式文章在后台替换，不要把别家官网的句子贴过来。</p>',
                'en' => '<p>'.$enEx.'</p><p>Placeholder. Replace it in the admin. Do not paste lines from another company site.</p>',
            ], $sort, now()->subDays(4 - $sort));
        }
    }

    private function modules(): void
    {
        $rec = Recommend::query()->updateOrCreate(
            ['name' => 'home_modules'],
            ['title' => '首页能力', 'type' => 'link', 'is_active' => true]
        );
        RecommendItem::query()->where('recommend_id', $rec->id)->delete();
        $rows = [
            ['支付', 'Payments', '按目标市场接渠道。支持的方式待补充。', 'Channels follow the market. The list is not filled in yet.'],
            ['代理', 'Agents', '层级和佣金可以配置。', 'Levels and commission can be set.'],
            ['活动', 'Promotions', '注册、充值、签到这一类可以建。', 'Signup, deposit, and check-in can be created.'],
            ['返水', 'Rebates', '规则按方案约定，细则待补充。', 'Rules follow the package. Detail is not filled in yet.'],
            ['风控', 'Risk', '异常行为有记录和提醒。', 'Unusual activity is recorded and flagged.'],
            ['报表', 'Reports', '按时间和代理看营收。', 'Revenue by time and by agent.'],
        ];
        foreach ($rows as $i => [$zh, $en, $zhNote, $enNote]) {
            RecommendItem::query()->create([
                'recommend_id' => $rec->id,
                'title' => ['zh_cn' => $zh, 'en' => $en],
                'notes' => ['zh_cn' => $zhNote, 'en' => $enNote],
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function markets(): void
    {
        $rec = Recommend::query()->updateOrCreate(
            ['name' => 'home_markets'],
            ['title' => '首页市场', 'type' => 'link', 'is_active' => true]
        );
        RecommendItem::query()->where('recommend_id', $rec->id)->delete();
        foreach ([
            ['东南亚', 'Southeast Asia', '越南、泰国、印尼、菲律宾', 'Vietnam, Thailand, Indonesia, the Philippines'],
            ['东亚', 'East Asia', '日本、韩国', 'Japan, Korea'],
            ['南亚', 'South Asia', '印度、孟加拉', 'India, Bangladesh'],
            ['拉美', 'Latin America', '先看巴西', 'Brazil first'],
        ] as $i => [$zh, $en, $zhNote, $enNote]) {
            RecommendItem::query()->create([
                'recommend_id' => $rec->id,
                'title' => ['zh_cn' => $zh, 'en' => $en],
                'notes' => ['zh_cn' => $zhNote, 'en' => $enNote],
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function form(): void
    {
        CmsForm::query()->updateOrCreate(
            ['slug' => 'gw-inquiry'],
            [
                'name' => '开站咨询',
                'is_active' => true,
                'notify_email' => null,
                'fields' => [
                    ['name' => 'name', 'label' => ['zh_cn' => '姓名', 'en' => 'Name'], 'type' => 'text', 'required' => true],
                    ['name' => 'telegram', 'label' => ['zh_cn' => 'Telegram', 'en' => 'Telegram'], 'type' => 'text', 'required' => true, 'placeholder' => '@username'],
                    ['name' => 'market', 'label' => ['zh_cn' => '目标市场', 'en' => 'Market'], 'type' => 'select', 'required' => true, 'choices' => ['东南亚 SEA', '东亚 East Asia', '南亚 South Asia', '拉美 Latin America', '其他 Other']],
                    ['name' => 'need', 'label' => ['zh_cn' => '需求类型', 'en' => 'Package'], 'type' => 'select', 'required' => true, 'choices' => ['标准包网 Standard', '定制包网 Custom', '游戏 API', '还没定 Not sure']],
                    ['name' => 'message', 'label' => ['zh_cn' => '补充说明', 'en' => 'Notes'], 'type' => 'textarea', 'required' => false],
                ],
            ]
        );
    }

    private function menu(): void
    {
        $menu = Menu::query()->updateOrCreate(
            ['location' => 'header'],
            ['name' => '主导航', 'is_active' => true]
        );
        MenuItem::query()->where('menu_id', $menu->id)->delete();
        $slugs = ['gw-solutions', 'gw-api', 'gw-platform', 'gw-markets', 'gw-insights', 'gw-faq', 'gw-about', 'gw-contact'];
        foreach ($slugs as $i => $slug) {
            $cat = $this->category($slug);
            MenuItem::query()->create([
                'menu_id' => $menu->id,
                'title' => $cat->title,
                'type' => 'category',
                'reference_id' => $cat->id,
                'url' => $cat->frontUrl(),
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    /** @param  array<string, string>  $title @param  array<string, string>  $slug @param  array<string, string>  $excerpt @param  array<string, string>  $content */
    private function put(Category $cat, ContentModel $model, string $guid, array $title, array $slug, array $excerpt, array $content, int $sort = 0, mixed $publishedAt = null): void
    {
        $entry = Entry::query()->updateOrCreate(
            ['source_guid' => $guid],
            [
                'category_id' => $cat->id,
                'model_id' => $model->id,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'status' => 'published',
                'sort_order' => $sort,
                'published_at' => $publishedAt ?? now()->subDay(),
            ]
        );
        $entry->rebuildSearchText();
        $entry->save();
    }

    private function category(string $slug): Category
    {
        $found = Category::query()->get()->first(function (Category $cat) use ($slug) {
            return in_array($slug, array_values((array) $cat->slug), true);
        });
        if (! $found) {
            throw new \RuntimeException('缺少栏目 '.$slug);
        }

        return $found;
    }
}
