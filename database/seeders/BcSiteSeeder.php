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
            'en' => 'For teams that already have a site and only need the games.',
        ], [
            'zh_cn' => '<p>游戏 API 不更换整套前台。对接一次之后，按品类开关。供应商数量、游戏数量和接入周期不写在这页上。</p><table class="ig-sheet"><thead><tr><th>品类</th><th>运营商得到什么</th><th>还没确认</th></tr></thead><tbody><tr><td>真人</td><td>现场桌台接到现有前台，可以按桌或按游戏开关。</td><td>供应商名单</td></tr><tr><td>体育</td><td>赛事盘口接到现有前台，和真人、电子用同一次对接。</td><td>联赛覆盖</td></tr><tr><td>电子</td><td>老虎机、捕鱼一类按品类开关，不单独重做前台。</td><td>游戏款数</td></tr><tr><td>棋牌</td><td>牌桌类按品类开关。</td><td>玩法名单</td></tr><tr><td>彩票</td><td>彩种按品类开关。</td><td>当地彩种</td></tr></tbody></table>',
            'en' => '<p>The game API does not replace an existing site. After one connection, categories can be switched on. Supplier count, game count, and timing are not on this page.</p><table class="ig-sheet"><thead><tr><th>Category</th><th>What you get</th><th>Not listed here</th></tr></thead><tbody><tr><td>Live</td><td>Live tables on the current site, switched by table or by game.</td><td>Supplier list</td></tr><tr><td>Sports</td><td>Odds on the current site, on the same connection as live and slots.</td><td>League coverage</td></tr><tr><td>Slots</td><td>Slots and fishing games switched by category. No new template.</td><td>Title count</td></tr><tr><td>Table games</td><td>Card rooms switched by category.</td><td>Game list</td></tr><tr><td>Lottery</td><td>Draws switched by category.</td><td>Local draws</td></tr></tbody></table>',
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
            'zh_cn' => '<p>这些是招商站要讲清的后台能力。具体规则在方案里约定。没核实的名单不写在这里。</p><table class="ig-sheet"><thead><tr><th>能力</th><th>现在能说的</th><th>还没确认</th></tr></thead><tbody><tr><td>支付</td><td>按目标市场接渠道。</td><td>具体方式</td></tr><tr><td>代理</td><td>层级和佣金可以配置。</td><td>默认层级</td></tr><tr><td>活动</td><td>注册、充值、签到这一类可以建。</td><td>活动模板数量</td></tr><tr><td>返水</td><td>规则按方案约定。</td><td>自动还是人工</td></tr><tr><td>风控</td><td>异常行为有记录和提醒。</td><td>规则清单</td></tr><tr><td>报表</td><td>按时间和代理看营收。</td><td>导出格式</td></tr><tr><td>语言货币</td><td>一个后台管多种。</td><td>具体清单</td></tr></tbody></table>',
            'en' => '<p>These are the admin areas. Exact rules belong in the agreement. Lists that are not settled are not written here.</p><table class="ig-sheet"><thead><tr><th>Area</th><th>What is covered</th><th>Not listed here</th></tr></thead><tbody><tr><td>Payments</td><td>Channels follow the market.</td><td>The method list</td></tr><tr><td>Agents</td><td>Levels and commission can be set.</td><td>A default tree</td></tr><tr><td>Promotions</td><td>Signup, deposit, and check-in can be created.</td><td>How many templates</td></tr><tr><td>Rebates</td><td>Rules follow the package.</td><td>Automatic or manual</td></tr><tr><td>Risk</td><td>Unusual activity is recorded and flagged.</td><td>The rule list</td></tr><tr><td>Reports</td><td>Revenue by time and by agent.</td><td>Export format</td></tr><tr><td>Language</td><td>One admin can hold several.</td><td>The actual list</td></tr></tbody></table>',
        ]);

        $this->put($this->category('gw-markets'), $page, 'bc:markets', [
            'zh_cn' => '目标市场',
            'en' => 'Markets',
        ], [
            'zh_cn' => 'markets',
            'en' => 'markets',
        ], [
            'zh_cn' => '第一版只做区域，不拆成十个国家页。',
            'en' => 'Four regions. Not a separate page for each country.',
        ], [
            'zh_cn' => '<p>上线前先确认目标市场允许这种业务，以及需要哪一种许可。这一版只做四个区域，不拆成国家页。五个品类在每个区域都可以按开关打开。某个国家专有的玩法名、以及具体支付渠道，不在这页列成已经接好的名单。</p><table class="ig-sheet"><thead><tr><th>区域</th><th>覆盖</th><th>语言</th><th>支付</th><th>可开品类</th></tr></thead><tbody><tr><td>东南亚</td><td>越南、泰国、印尼、菲律宾、柬埔寨、缅甸</td><td>越南语、泰语、印尼语、英语、菲律宾语、高棉语、缅甸语</td><td>按该区域接渠道，名单在方案里定</td><td>真人、体育、电子、棋牌、彩票</td></tr><tr><td>东亚</td><td>日本、韩国</td><td>日语、韩语</td><td>按该区域接渠道，名单在方案里定</td><td>真人、体育、电子、棋牌、彩票</td></tr><tr><td>南亚</td><td>印度、孟加拉</td><td>印地语、英语、孟加拉语</td><td>按该区域接渠道，名单在方案里定</td><td>真人、体育、电子、棋牌、彩票</td></tr><tr><td>拉美</td><td>巴西为主</td><td>葡萄牙语</td><td>按该区域接渠道，名单在方案里定</td><td>真人、体育、电子、棋牌、彩票</td></tr></tbody></table>',
            'en' => '<p>Before launch, confirm the market allows the business and which licence it needs. This version is four regions, not a page per country. The same five categories can be switched on in each region. Country-specific game names and the exact payment channels are not listed here as already connected.</p><table class="ig-sheet"><thead><tr><th>Region</th><th>Coverage</th><th>Language</th><th>Payments</th><th>Categories</th></tr></thead><tbody><tr><td>Southeast Asia</td><td>Vietnam, Thailand, Indonesia, the Philippines, Cambodia, Myanmar</td><td>Vietnamese, Thai, Indonesian, English, Filipino, Khmer, Burmese</td><td>Channels for the region are set in the package</td><td>Live, sports, slots, table games, lottery</td></tr><tr><td>East Asia</td><td>Japan, Korea</td><td>Japanese, Korean</td><td>Channels for the region are set in the package</td><td>Live, sports, slots, table games, lottery</td></tr><tr><td>South Asia</td><td>India, Bangladesh</td><td>Hindi, English, Bengali</td><td>Channels for the region are set in the package</td><td>Live, sports, slots, table games, lottery</td></tr><tr><td>Latin America</td><td>Mainly Brazil</td><td>Portuguese</td><td>Channels for the region are set in the package</td><td>Live, sports, slots, table games, lottery</td></tr></tbody></table>',
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
            'zh_cn' => '<p>IG包网把前台、后台和游戏接入交给准备开站的团队。前台有九套，后台和代理台不跟着换皮。</p><h2>三档</h2><p>标准包网用现有模板，改名字、Logo 和主色。定制包网按确认过的稿改界面和流程。游戏 API 不换现有前台，按品类接真人、体育、电子、棋牌和彩票。</p><p>办公地点和客户案例还没放到这页。市场和档位用<a href="/gw-contact">咨询</a>说。</p>',
            'en' => '<p>IG delivers the template, the admin, and the game connection to teams preparing a launch. There are nine templates. Changing the template does not change the admin or the agent desk.</p><h2>Three packages</h2><p>Standard keeps a current template and changes the name, logo, and color. Custom changes layout and flow from an approved design. The game API keeps the current site and connects live, sports, slots, table games, and lottery by category.</p><p>Office details and client cases are not on this page yet. The market and the package go through <a href="/gw-contact">contact</a>.</p>',
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
            'zh_cn' => '<h2>包网和自己开发差在哪？</h2><p>包网用现成系统上线。自己开发要单独做前台、后台和接入，时间更长。</p><h2>标准和定制差在哪？</h2><p>标准用现有模板，改品牌元素。定制改界面和流程，周期更长。天数不写在页面上。</p><h2>上线要准备什么？</h2><p>品牌名、Logo、对接人，以及目标市场。支付和游戏清单在方案里定。</p><h2>有公开报价吗？</h2><p>没有。按档位和改动范围单独报，页面上不放参考价，也不列费率。</p><h2>可以改界面吗？</h2><p>标准只改名字、Logo 和主色。要改页面结构和操作路径，走定制，按确认过的稿。</p><h2>有没有额外费用？</h2><p>报价只按档位和写进范围的改动。范围外的改动另计。页面不列一份价目。</p><h2>能多币种吗？</h2><p>一个后台可以管多种语言和货币。具体清单按市场确认，没确认的不写在这里。</p><h2>能后加支付吗？</h2><p>可以。渠道按当时的市场和方案另约，这页不承诺某一种支付已经接好。</p><h2>可以看演示吗？</h2><p>首页可以看九套前台和后台示意。可登录的演示账号还没有，有了再改这句。</p><h2>上线后谁维护？</h2><p>按合同里的维护范围。页面不承诺具体响应时间。</p>',
            'en' => '<h2>How is this different from building it?</h2><p>A package launches on a system that already exists. Building it means a new member site, a new admin, and new connections, and it takes longer.</p><h2>Standard or custom?</h2><p>Standard keeps the current templates and changes the brand. Custom changes layout and flow, and takes longer. Timelines are not on this page.</p><h2>What should you send?</h2><p>Brand name, logo, a contact, and the target market. Payments and the game list are set in the package.</p><h2>Is there a public price?</h2><p>No. Price follows the package and the amount of change. Rates are not listed here.</p><h2>Can the layout change?</h2><p>Standard changes the name, logo, and color. Structure and paths are the custom package, from an approved design.</p><h2>Are there extra fees?</h2><p>The quote covers the package and changes written into the scope. Work outside that scope is quoted again. There is no price list on this page.</p><h2>More than one currency?</h2><p>One admin can hold several languages and currencies. The list is confirmed for each market.</p><h2>Can a payment method be added later?</h2><p>Yes. Channels are agreed for that market and package at the time. This page does not list a method as already connected.</p><h2>Is there a demo?</h2><p>The homepage shows nine templates and a sample admin. A login demo is not ready.</p><h2>Who maintains it?</h2><p>It follows the agreement. A response time is not stated on this page.</p>',
        ]);

        $this->put($this->category('gw-contact'), $page, 'bc:contact', [
            'zh_cn' => '联系我们',
            'en' => 'Contact',
        ], [
            'zh_cn' => 'contact',
            'en' => 'contact',
        ], [
            'zh_cn' => '先留 Telegram 和目标市场。',
            'en' => 'Leave your Telegram handle and the target market.',
        ], [
            'zh_cn' => '<p>写下姓名、Telegram、目标市场，以及要标准、定制还是游戏 API。报价和上线周期不在这页，提交后单独回。</p>',
            'en' => '<p>Leave a name, Telegram, the market, and whether you want standard, custom, or the game API. Price and timing are not on this page. We send the price and the timing after you submit it.</p>',
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
                '<p>下表是这一档的范围。周期不写在页面上，按改动另估。</p><table class="ig-sheet"><thead><tr><th>包含</th><th>不包含</th><th>需要客户提供</th></tr></thead><tbody><tr><td>九套模板里选一套前台。管理后台、代理台。按市场接游戏。约定范围内的维护。</td><td>按稿重做界面。单独的游戏规则。</td><td>品牌名、Logo、主色、对接人、目标市场。</td></tr></tbody></table>',
                '<p>This table is the scope of this package. Timing is not on the page. It is estimated from the changes.</p><table class="ig-sheet"><thead><tr><th>Included</th><th>Not included</th><th>You provide</th></tr></thead><tbody><tr><td>One of the nine templates. Admin and agent. Games for the market. Maintenance inside the agreement.</td><td>A layout rebuilt from a design. Rules written for one game.</td><td>Brand name, logo, main color, a contact, and the market.</td></tr></tbody></table>',
            ],
            [
                'bc:pkg-custom', 20, '定制包网', 'Custom package', 'custom',
                '按品牌改界面和流程，适合已经知道要长什么样的团队。',
                'Layout and flow follow the brand. For teams that already know what it should look like.',
                '<p>下表是这一档的范围。周期不写在页面上，按改动另估。</p><table class="ig-sheet"><thead><tr><th>包含</th><th>不包含</th><th>需要客户提供</th></tr></thead><tbody><tr><td>标准方案里的前台、后台和代理台。按确认过的稿改界面和流程。约定范围内的维护。</td><td>稿面之外的改动。没有写进范围的游戏规则。</td><td>确认过的设计稿、品牌名、Logo、对接人、目标市场。</td></tr></tbody></table>',
                '<p>This table is the scope of this package. Timing is not on the page. It is estimated from the changes.</p><table class="ig-sheet"><thead><tr><th>Included</th><th>Not included</th><th>You provide</th></tr></thead><tbody><tr><td>The standard template, admin, and agent. Layout and flow from an approved design. Maintenance inside the agreement.</td><td>Changes outside the approved design. Game rules that were not written into the scope.</td><td>An approved design, brand name, logo, a contact, and the market.</td></tr></tbody></table>',
            ],
            [
                'bc:pkg-api', 10, '游戏 API', 'Game API', 'api',
                '不换整套前台，只把游戏接到已有站点。',
                'Keep the current site. Connect games to a site that already exists.',
                '<p>下表是这一档的范围。周期不写在页面上，按改动另估。</p><table class="ig-sheet"><thead><tr><th>包含</th><th>不包含</th><th>需要客户提供</th></tr></thead><tbody><tr><td>一次对接。真人、体育、电子、棋牌、彩票按品类开关。接入说明和联调。</td><td>新的品牌前台。整套管理后台。代理台。</td><td>现有站点的对接人、要开的品类、目标市场。</td></tr></tbody></table>',
                '<p>This table is the scope of this package. Timing is not on the page. It is estimated from the changes.</p><table class="ig-sheet"><thead><tr><th>Included</th><th>Not included</th><th>You provide</th></tr></thead><tbody><tr><td>One connection. Live, sports, slots, table games, and lottery switched by category. Notes and a joint test.</td><td>A new branded front. The full admin. The agent desk.</td><td>A contact for the current site, the categories to switch on, and the market.</td></tr></tbody></table>',
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
            [
                'bc:post-what', 5, '包网实际交什么', 'What a white-label launch includes', 'what-you-get',
                '前台、后台、代理台和游戏接入。不是只交一个登录页。',
                'The template, the admin, the agent desk, and the games. More than a login page.',
                '<p>包网是把已经做好的系统交给准备开站的团队，用自己的品牌对外。你不用从零写前台、后台和游戏接入。自己开发要把这三块分开做，时间更长。</p><h2>前台</h2><p>会员看到的站点。IG 有九套，结构不同，材质同一套。标准方案从里面选一套，换名字、Logo 和主色。换皮不改后台和代理台。</p><h2>后台</h2><p>运营每天用的桌子：会员、注单、财务、活动、公告，以及模板切换。支付按目标市场接，代理层级和佣金可以配置，报表按时间和代理看营收。具体渠道和规则写进方案，不在文章里报成已经接好。</p><h2>代理台</h2><p>和下级、占成、报表、推广链接有关，和会员前台分开登录。</p><h2>游戏</h2><p>真人、体育、电子、棋牌、彩票可以按品类开关。供应商数量和游戏款数还没写死，文章里不报一个整数。</p><p>三档的差别看<a href="/gw-solutions">方案</a>。整站用标准或定制。已有站点只补游戏，用游戏 API。</p>',
                '<p>A white-label launch hands over a system that already exists, under your brand. You do not build the member site, the admin, and the game connections from scratch. Building those three separately takes longer.</p><h2>Front</h2><p>What members see. IG has nine templates. Standard picks one and changes the name, logo, and color. A new template does not rebuild the admin or the agent desk.</p><h2>Admin</h2><p>The desk operators use: members, bets, finance, promotions, notices, and the template switch. Payments follow the market. Agent levels and commission can be set. Reports show revenue by time and by agent. Channels and rules go in the package. This article does not treat them as already connected.</p><h2>Agent</h2><p>Downline, share, reports, and promo links. A separate login from the member site.</p><h2>Games</h2><p>Live, sports, slots, table games, and lottery can be switched by category. Supplier count and title count are not stated here.</p><p>The three packages are on <a href="/gw-solutions">packages</a>. A whole site is standard or custom. A site that already exists and only needs games uses the game API.</p>',
            ],
            [
                'bc:post-pick', 4, '标准、定制、游戏 API 怎么选', 'How to pick a package', 'which-package',
                '先看你有没有站点，再看界面要不要按稿重做。',
                'Two questions: does a site already exist, and is the layout already designed?',
                '<p>先问两句：现在有没有自己的站点，界面有没有确认过的稿。</p><h2>没有站点，界面也还没定</h2><p>用<a href="/gw-solutions/standard">标准包网</a>。九套里选一套，改品牌元素。包含前台、后台、代理台，以及按市场接游戏。不包含按稿重做界面。</p><h2>稿已经定了</h2><p>用<a href="/gw-solutions/custom">定制包网</a>。在标准方案上按确认过的稿改页面结构和操作路径。稿面以外的改动另计，不写进默认范围。</p><h2>站点已经在跑，只缺游戏</h2><p>用<a href="/gw-solutions/api">游戏 API</a>。一次对接，按品类开关。不交新的品牌前台，也不交整套后台和代理台。</p><p>三档都是同一张表：包含、不包含、需要客户提供。天数和费率不写在页面上，范围定了再报。</p>',
                '<p>Two questions: is there already a site, and is there an approved design.</p><h2>No site, and no final layout</h2><p>Use <a href="/gw-solutions/standard">standard</a>. Pick one of nine and change the brand. Front, admin, agent, and games for the market are included. A layout rebuilt from a design is not.</p><h2>The design is approved</h2><p>Use <a href="/gw-solutions/custom">custom</a>. Structure and paths change from that design, on top of standard. Work outside the design is quoted again.</p><h2>The site is live and only games are missing</h2><p>Use the <a href="/gw-solutions/api">game API</a>. One connection, switched by category. No new template, and no full admin or agent desk.</p><p>All three use the same table: included, not included, what you provide. Timelines and rates are not on the page.</p>',
            ],
            [
                'bc:post-check', 3, '开站前先确认的五件事', 'Five checks before a launch', 'five-checks',
                '市场、档位、品牌素材、对接人、许可。',
                'Market, package, brand files, a contact, and the licence.',
                '<p>报价之前，这五件先对齐。缺一件，后面的范围表会对不上。</p><h2>市场</h2><p>四个区域里先定一个：东南亚、东亚、南亚或拉美。覆盖的国家写在<a href="/gw-markets">目标市场</a>。语言、支付和当地玩法还标着待确认的，不要当成已经接好。</p><h2>档位</h2><p>标准、定制或游戏 API。看<a href="/gw-solutions">方案</a>里的范围表，不要只看名字。</p><h2>品牌素材</h2><p>名字、Logo、主色，外加一句话介绍。定制才另交确认过的设计稿。</p><h2>对接人</h2><p>一个能回 Telegram 的人，负责品牌、市场和验收。游戏 API 还要能对上现有站点的技术对接。</p><h2>许可</h2><p>上线前由你确认目标市场允许这种业务，以及需要哪一种许可。IG 不在这篇文章里代办牌照。</p>',
                '<p>Line these five up before a quote. If one is missing, the scope table will not match.</p><h2>Market</h2><p>Pick one of four regions. Countries are listed on <a href="/gw-markets">markets</a>. Language, payments, and local games marked not confirmed are not already connected.</p><h2>Package</h2><p>Standard, custom, or game API. Read the scope table on <a href="/gw-solutions">packages</a>, not only the name.</p><h2>Brand files</h2><p>Name, logo, main color, and one sentence. Custom also needs an approved design.</p><h2>A contact</h2><p>Someone who answers on Telegram for brand, market, and acceptance. The API package also needs a technical contact on the current site.</p><h2>Licence</h2><p>You confirm that the market allows the business, and which licence it needs. This article does not file a licence.</p>',
            ],
            [
                'bc:post-standard', 2, '什么时候用标准方案', 'When the standard package fits', 'when-standard',
                '还没有定稿界面、想先上线时用标准方案。',
                'Use it when the layout is not final and you want to launch first.',
                '<p>标准方案适合还没有自己的前台、界面也还没定稿的团队。从九套里选一间，先把品牌换上，后台和代理台一起交付。</p><h2>你得到的</h2><p>一套现成前台，电脑和手机同一套结构。管理后台和代理台。游戏按市场接入，品类是真人、体育、电子、棋牌、彩票。维护按合同里的范围，文章不写响应小时数。</p><h2>你先交的</h2><p>品牌名、Logo、主色、对接人、目标市场。支付和游戏的具体清单在方案里定，不在这篇里列厂商名。</p><h2>不要用标准的时候</h2><p>要按稿改页面结构或操作路径，走<a href="/gw-solutions/custom">定制</a>。站点已经在跑、只补游戏，走<a href="/gw-solutions/api">游戏 API</a>。标准短于定制，但天数等范围定了再报。</p>',
                '<p>Standard fits a team with no site of its own and no final layout. Pick one of nine, put the brand on it, and take the admin and agent desk with it.</p><h2>What you get</h2><p>One existing template, the same structure on desktop and mobile. Admin and agent. Games for the market: live, sports, slots, table games, lottery. Maintenance follows the agreement. This article does not state a response time.</p><h2>What you send first</h2><p>Brand name, logo, main color, a contact, and the market. The payment and game lists are set in the package. Vendor names are not listed here.</p><h2>When not to use it</h2><p>Changing structure or paths from a design is <a href="/gw-solutions/custom">custom</a>. A live site that only needs games is the <a href="/gw-solutions/api">game API</a>. Standard is shorter than custom. The timeline waits until the scope is set.</p>',
            ],
            [
                'bc:post-files', 1, '品牌素材要准备哪些', 'Brand files to prepare', 'brand-files',
                '名字、Logo、主色，以及一句话介绍。',
                'Name, logo, a main color, and one sentence.',
                '<p>标准方案开站，先交下面这些。文件还没齐，也可以先选模板，但名字和颜色空着时前台还是示例。</p><ul><li>品牌名。前台页头、后台和代理台用同一个名字。</li><li>Logo。用在页头。底是深色，浅色标更清楚。</li><li>主色。按钮和重点文字跟着它走，九套厅的底色不跟着改。</li><li>一句话介绍。放在页头或页脚，说明这是你的品牌，不是 IG 的演示厅。</li></ul><p>对接人和目标市场跟素材一起给。成套设计稿、多尺寸海报、活动图不是标准方案的材料。那些走定制，按确认过的稿另计。</p>',
                '<p>For a standard launch, send the items below. You can pick a template first, but an empty name and color leave the front looking like the sample.</p><ul><li>Brand name. The same name on the front, in the admin, and on the agent desk.</li><li>Logo. It sits in the header. The page is dark, so a light mark reads more clearly.</li><li>Main color. Buttons and emphasis follow it. The dark ground of the nine rooms stays.</li><li>One sentence. Header or footer, so the hall reads as your brand.</li></ul><p>Send a contact and the market with the files. A full design, a poster set, and campaign art are not standard-package files. Those are custom, from an approved design.</p>',
            ],
        ];
        foreach ($rows as [$guid, $sort, $zh, $en, $slug, $zhEx, $enEx, $zhHtml, $enHtml]) {
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
                'zh_cn' => $zhHtml,
                'en' => $enHtml,
            ], $sort, now()->subDays(6 - $sort));
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
            ['支付', 'Payments', '按目标市场接渠道。具体方式在方案里定。', 'Channels follow the market. The list is set in the package.'],
            ['代理', 'Agents', '层级和佣金可以配置。', 'Levels and commission can be set.'],
            ['活动', 'Promotions', '注册、充值、签到这一类可以建。', 'Signup, deposit, and check-in can be created.'],
            ['返水', 'Rebates', '规则按方案约定。', 'Rules follow the package.'],
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
            ['拉美', 'Latin America', '先看巴西', 'Mainly Brazil'],
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
                    ['name' => 'market', 'label' => ['zh_cn' => '目标市场', 'en' => 'Market'], 'type' => 'select', 'required' => true, 'choices' => [
                        ['value' => 'Southeast Asia', 'zh_cn' => '东南亚', 'en' => 'Southeast Asia'],
                        ['value' => 'East Asia', 'zh_cn' => '东亚', 'en' => 'East Asia'],
                        ['value' => 'South Asia', 'zh_cn' => '南亚', 'en' => 'South Asia'],
                        ['value' => 'Latin America', 'zh_cn' => '拉美', 'en' => 'Latin America'],
                        ['value' => 'Other', 'zh_cn' => '其他', 'en' => 'Other'],
                    ]],
                    ['name' => 'need', 'label' => ['zh_cn' => '需求类型', 'en' => 'Package'], 'type' => 'select', 'required' => true, 'choices' => [
                        ['value' => 'Standard', 'zh_cn' => '标准包网', 'en' => 'Standard'],
                        ['value' => 'Custom', 'zh_cn' => '定制包网', 'en' => 'Custom'],
                        ['value' => 'Game API', 'zh_cn' => '游戏 API', 'en' => 'Game API'],
                        ['value' => 'Not sure', 'zh_cn' => '还没定', 'en' => 'Not sure'],
                    ]],
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
