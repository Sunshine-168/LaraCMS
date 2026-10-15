@extends('themes.bc.partials.layout')

@php
    $front = app(\App\Services\Cms\SiteFrontService::class);
    $solutionsUrl = $front->categoryBySlug('gw-solutions')?->frontUrl() ?: cms_front_url('gw-solutions');
    $insightsUrl = $front->categoryBySlug('gw-insights')?->frontUrl() ?: cms_front_url('gw-insights');
    $marketsUrl = $front->categoryBySlug('gw-markets')?->frontUrl() ?: cms_front_url('gw-markets');
    $apiUrl = $front->categoryBySlug('gw-api')?->frontUrl() ?: cms_front_url('gw-api');
    $faqUrl = $front->categoryBySlug('gw-faq')?->frontUrl() ?: cms_front_url('gw-faq');
    $platformUrl = $front->categoryBySlug('gw-platform')?->frontUrl() ?: cms_front_url('gw-platform');
    $solutions = app(\App\Services\Cms\Tags\ContentTag::class)->get(['model' => 'product', 'num' => 3]);
    $news = app(\App\Services\Cms\Tags\ContentTag::class)->get(['model' => 'article', 'num' => 3, 'order' => 'published_at desc']);
@endphp

@section('content')
    <section class="ig-hero">
        <div class="ig-hero-copy">
            <p class="ig-kicker">IG {{ cms_t('包网', 'White-label') }}</p>
            <h1>{!! cms_t('用你的品牌<br>上线一整套平台', 'Launch a full platform<br>under your brand') !!}</h1>
            <p class="ig-lead">{{ cms_t('九套前台里选一套，换成你的名字和 Logo。后台、代理台不动。标准、定制，或只接游戏 API。', 'Pick one of nine templates and put your name and logo on it. The admin and the agent desk stay as they are. Then choose standard, custom, or the game API only.') }}</p>
            <div class="ig-actions">
                <a class="ig-btn ig-btn-solid" href="#templates">{{ cms_t('看九套前台', 'See the templates') }}</a>
                <a class="ig-btn" href="#contact">{{ cms_t('咨询开通', 'Contact') }}</a>
            </div>
            <div class="ig-hero-stats">
                <div><b>9</b><span>{{ cms_t('套前台', 'Templates') }}</span></div>
                <div><b>5</b><span>{{ cms_t('类游戏', 'Game types') }}</span></div>
                <div><b>3</b><span>{{ cms_t('端管理', 'Player, admin, agent') }}</span></div>
            </div>
        </div>
        <div class="ig-hero-stage">
            @include('themes.bc.partials.showcase', ['only' => 'harbor', 'shotOnly' => true])
        </div>
    </section>

    <section class="ig-note" id="demos">
        <div class="ig-wrap ig-note-row">
            <strong>{{ cms_t('演示只看界面', 'Preview only') }}</strong>
            <p>{{ cms_t('每套模板都带前台、后台、代理三端结构。这里不放商户密钥，也不要在演示里改数据。开通走页面底部的咨询。', 'Each template includes a front, an admin, and an agent desk. No merchant keys here. Use the form at the bottom.') }}</p>
        </div>
    </section>

    <section class="ig-gallery" id="templates">
        <div class="ig-wrap">
            <div class="ig-gallery-head">
                <div>
                    <p class="ig-kicker">{{ cms_t('前台', 'Templates') }}</p>
                    <h2>{{ cms_t('九套前台，一套后台。', 'Nine templates. One admin.') }}</h2>
                </div>
                <p>{{ cms_t('每套都能点开看。换的是前台结构，后台和代理台保持同一套。', 'Open any template. The layout changes. The admin and the agent desk stay the same.') }}</p>
            </div>
            <div class="ig-filters" role="tablist">
                <button type="button" class="is-on" data-filter="all">@include('themes.bc.partials.icon', ['name' => 'grid']){{ cms_t('全部 9', 'All 9') }}</button>
                <button type="button" data-filter="sports">@include('themes.bc.partials.icon', ['name' => 'ball']){{ cms_t('体育', 'Sports') }}</button>
                <button type="button" data-filter="live">@include('themes.bc.partials.icon', ['name' => 'table']){{ cms_t('真人', 'Live') }}</button>
                <button type="button" data-filter="slots">@include('themes.bc.partials.icon', ['name' => 'slots']){{ cms_t('电子', 'Slots') }}</button>
                <button type="button" data-filter="card">@include('themes.bc.partials.icon', ['name' => 'cards']){{ cms_t('棋牌彩票', 'Cards & lottery') }}</button>
            </div>
            <div class="ig-skins">
                @include('themes.bc.partials.showcase', ['only' => null, 'shotOnly' => false])
            </div>
        </div>
    </section>

    <section class="ig-section" id="fit">
        <div class="ig-wrap">
            <div class="ig-gallery-head">
                <div>
                    <p class="ig-kicker">{{ cms_t('适合谁', 'Who it’s for') }}</p>
                    <h2>{{ cms_t('三种团队。', 'Three situations.') }}</h2>
                </div>
                <p>{{ cms_t('先对上自己的情况，再往下看游戏和后台。', 'See which one matches you, then look at the games and the admin.') }}</p>
            </div>
            <div class="ig-fit">
                <a href="#templates">
                    @include('themes.bc.partials.icon', ['name' => 'grid'])
                    <h3>{{ cms_t('刚开站', 'New site') }}</h3>
                    <p>{{ cms_t('还没有自己的前台。从九套里选一间，先换名字、Logo 和主色。', 'No site of your own yet. Pick one of the nine and change the name, logo, and color.') }}</p>
                </a>
                <a href="#solutions">
                    @include('themes.bc.partials.icon', ['name' => 'refresh'])
                    <h3>{{ cms_t('已有盘', 'Already running') }}</h3>
                    <p>{{ cms_t('站点已经在跑。要换整套后台，或只把游戏接到现有前台。后一种走游戏 API。', 'The site is already live. Replace the admin, or only connect games to the current front. If you only need the games, use the API package.') }}</p>
                </a>
                <a href="{{ $marketsUrl }}">
                    @include('themes.bc.partials.icon', ['name' => 'globe'])
                    <h3>{{ cms_t('多市场', 'Several markets') }}</h3>
                    <p>{{ cms_t('一个后台管语言和货币。具体国家和支付方式在市场页核对。', 'One admin for languages and currencies. Countries and payment methods are checked on the markets page.') }}</p>
                </a>
            </div>
        </div>
    </section>

    <section class="ig-section" id="games">
        <div class="ig-wrap">
            <div class="ig-gallery-head">
                <div>
                    <p class="ig-kicker">{{ cms_t('游戏', 'Games') }}</p>
                    <h2>{{ cms_t('五类，接在同一套接口上。', 'Five categories, one connection.') }}</h2>
                </div>
                <p><a class="ig-textlink" href="{{ $apiUrl }}">{{ cms_t('看接口说明', 'API details') }}</a></p>
            </div>
            @include('themes.bc.partials.cats', ['apiUrl' => $apiUrl])
        </div>
    </section>

    <section class="ig-section ig-section-ink" id="console">
        <div class="ig-wrap ig-console">
            <div class="ig-console-copy">
                <p class="ig-kicker">{{ cms_t('后台 / 代理', 'Admin / Agent') }}</p>
                <h2>{{ cms_t('前台换皮，这两端不动。', 'New template. Same admin and agent.') }}</h2>
                <ul class="ig-points">
                    <li>@include('themes.bc.partials.icon', ['name' => 'desk'])<b>{{ cms_t('管理后台', 'Admin') }}</b><span>{{ cms_t('会员、注单、财务、活动、公告、模板切换。', 'Members, bets, finance, promotions, notices, and the template switch.') }}</span></li>
                    <li>@include('themes.bc.partials.icon', ['name' => 'users'])<b>{{ cms_t('代理台', 'Agent') }}</b><span>{{ cms_t('下级、占成、报表、推广链接。和会员前台分开。', 'Downline, revenue share, reports, and promo links. Separate from the member front.') }}</span></li>
                    <li>@include('themes.bc.partials.icon', ['name' => 'screen'])<b>{{ cms_t('前台', 'Front') }}</b><span>{{ cms_t('上面九套里选。名字、Logo、主色按你的品牌换。', 'Pick one of the nine. Name, logo, and color follow your brand.') }}</span></li>
                </ul>
            </div>
            <div class="ig-desk" aria-hidden="true">
                <div class="ig-desk-side">
                    <b>IG</b>
                    <span class="is-on">{{ cms_t('总览', 'Overview') }}</span>
                    <span>{{ cms_t('会员', 'Members') }}</span>
                    <span>{{ cms_t('注单', 'Bets') }}</span>
                    <span>{{ cms_t('财务', 'Finance') }}</span>
                    <span>{{ cms_t('代理', 'Agents') }}</span>
                </div>
                <div class="ig-desk-main">
                    <div class="ig-desk-top"><strong>{{ cms_t('今日总览', 'Today’s overview') }}</strong><em class="ig-sample">{{ cms_t('界面示例', 'Sample screen') }}</em></div>
                    <div class="ig-desk-metrics">
                        <div><small>{{ cms_t('充值', 'Deposits') }}</small><b>{{ cms_t('86.4万', '864k') }}</b></div>
                        <div><small>{{ cms_t('提现', 'Withdrawals') }}</small><b>{{ cms_t('41.2万', '412k') }}</b></div>
                        <div><small>{{ cms_t('注单', 'Bets') }}</small><b>12,603</b></div>
                    </div>
                    <div class="ig-desk-table">
                        <div><span>14:21</span><span>{{ cms_t('会员 8821', 'Member 8821') }}</span><span>{{ cms_t('体育', 'Sports') }}</span><b>1.86</b></div>
                        <div><span>14:18</span><span>{{ cms_t('代理 A12', 'Agent A12') }}</span><span>{{ cms_t('占成', 'Share') }}</span><b>+2,400</b></div>
                        <div><span>14:11</span><span>{{ cms_t('会员 1904', 'Member 1904') }}</span><span>{{ cms_t('真人', 'Live') }}</span><b>500</b></div>
                        <div><span>14:02</span><span>{{ cms_t('会员 6630', 'Member 6630') }}</span><span>{{ cms_t('提现', 'Cash out') }}</span><b>8,000</b></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="ig-wrap ig-mods">
            <div class="ig-gallery-head">
                <div>
                    <p class="ig-kicker">{{ cms_t('后台能力', 'Admin') }}</p>
                    <h2>{{ cms_t('七项。细则按市场约定。', 'Seven areas. The details are set with the market.') }}</h2>
                </div>
                <p><a class="ig-textlink" href="{{ $platformUrl }}">{{ cms_t('系统能力', 'Platform') }}</a></p>
            </div>
            <div class="ig-modgrid">
                <div>@include('themes.bc.partials.icon', ['name' => 'card'])<b>{{ cms_t('支付', 'Payments') }}</b><span>{{ cms_t('按目标市场接渠道，方式在方案里定。', 'Channels follow the market and are set in the package.') }}</span></div>
                <div>@include('themes.bc.partials.icon', ['name' => 'users'])<b>{{ cms_t('代理', 'Agents') }}</b><span>{{ cms_t('层级和佣金可以配置。', 'Levels and commission can be set.') }}</span></div>
                <div>@include('themes.bc.partials.icon', ['name' => 'gift'])<b>{{ cms_t('活动', 'Promotions') }}</b><span>{{ cms_t('注册、充值、签到这一类可以建。', 'Signup, deposit, and check-in can be created.') }}</span></div>
                <div>@include('themes.bc.partials.icon', ['name' => 'return'])<b>{{ cms_t('返水', 'Rebates') }}</b><span>{{ cms_t('规则按方案约定。', 'Rules follow the package.') }}</span></div>
                <div>@include('themes.bc.partials.icon', ['name' => 'shield'])<b>{{ cms_t('风控', 'Risk') }}</b><span>{{ cms_t('异常行为有记录和提醒。', 'Unusual activity is recorded and flagged.') }}</span></div>
                <div>@include('themes.bc.partials.icon', ['name' => 'chart'])<b>{{ cms_t('报表', 'Reports') }}</b><span>{{ cms_t('按时间和代理看营收。', 'Revenue by time and by agent.') }}</span></div>
                <div>@include('themes.bc.partials.icon', ['name' => 'lang'])<b>{{ cms_t('语言货币', 'Languages') }}</b><span>{{ cms_t('一个后台管多种，清单按市场定。', 'One admin for several languages and currencies. The list follows the market.') }}</span></div>
            </div>
        </div>
    </section>

    @if($solutions->isNotEmpty())
        <section class="ig-section" id="solutions">
            <div class="ig-wrap">
                <div class="ig-gallery-head">
                    <div>
                        <p class="ig-kicker">{{ cms_t('方案', 'Packages') }}</p>
                        <h2>{{ cms_t('模板选定之后，再选交付档。', 'Choose a template, then a package.') }}</h2>
                    </div>
                    <p><a class="ig-textlink" href="{{ $solutionsUrl }}">{{ cms_t('三档交付范围', 'Three packages') }}</a></p>
                </div>
                <div class="ig-packs">
                    @foreach($solutions as $item)
                        <a class="ig-pack" href="{{ $item->url }}">
                            @include('themes.bc.partials.shot', ['slug' => $item->t('slug'), 'alt' => $item->t('title')])
                            <div>
                                <h3>{{ $item->t('title') }}</h3>
                                <p>{{ $item->t('excerpt') }}</p>
                                @include('themes.bc.partials.scope', ['slug' => $item->t('slug')])
                                <em>{{ cms_t('查看这一档', 'See this package') }}</em>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="ig-section" style="padding-top:0">
        <div class="ig-wrap">
            <p class="ig-kicker">{{ cms_t('开通', 'Launch') }}</p>
            <h2>{{ cms_t('四步。', 'Four steps.') }}</h2>
            <div class="ig-steps">
                <div class="ig-step">@include('themes.bc.partials.icon', ['name' => 'grid'])<b>01</b><strong>{{ cms_t('选模板', 'Template') }}</strong><span>{{ cms_t('从上面九套里定前台结构。', 'Pick one of the nine templates.') }}</span></div>
                <div class="ig-step">@include('themes.bc.partials.icon', ['name' => 'pen'])<b>02</b><strong>{{ cms_t('交品牌', 'Brand') }}</strong><span>{{ cms_t('名字、Logo、主色、对接人。', 'Name, logo, color, and a contact.') }}</span></div>
                <div class="ig-step">@include('themes.bc.partials.icon', ['name' => 'link'])<b>03</b><strong>{{ cms_t('对接口', 'Connect') }}</strong><span>{{ cms_t('游戏、后台、代理台一起验收。', 'Review the games, the admin, and the agent desk together.') }}</span></div>
                <div class="ig-step">@include('themes.bc.partials.icon', ['name' => 'check'])<b>04</b><strong>{{ cms_t('上线', 'Live') }}</strong><span>{{ cms_t('按约定维护，模板可以再换。', 'Maintenance as agreed. The template can change later.') }}</span></div>
            </div>
        </div>
    </section>

    <section class="ig-section" id="regions" style="padding-top:0">
        <div class="ig-wrap">
            <div class="ig-gallery-head">
                <div>
                    <p class="ig-kicker">{{ cms_t('市场', 'Markets') }}</p>
                    <h2>{{ cms_t('四个区域。', 'Four regions.') }}</h2>
                </div>
                <p><a class="ig-textlink" href="{{ $marketsUrl }}">{{ cms_t('语言和可开品类', 'Languages and categories') }}</a></p>
            </div>
            <div class="ig-regions">
                <a href="{{ $marketsUrl }}">@include('themes.bc.partials.icon', ['name' => 'waves'])<b>{{ cms_t('东南亚', 'Southeast Asia') }}</b><span>{{ cms_t('越南、泰国、印尼、菲律宾、柬埔寨、缅甸', 'Vietnam, Thailand, Indonesia, the Philippines, Cambodia, Myanmar') }}</span></a>
                <a href="{{ $marketsUrl }}">@include('themes.bc.partials.icon', ['name' => 'sun'])<b>{{ cms_t('东亚', 'East Asia') }}</b><span>{{ cms_t('日本、韩国', 'Japan, Korea') }}</span></a>
                <a href="{{ $marketsUrl }}">@include('themes.bc.partials.icon', ['name' => 'peak'])<b>{{ cms_t('南亚', 'South Asia') }}</b><span>{{ cms_t('印度、孟加拉', 'India, Bangladesh') }}</span></a>
                <a href="{{ $marketsUrl }}">@include('themes.bc.partials.icon', ['name' => 'pin'])<b>{{ cms_t('拉美', 'Latin America') }}</b><span>{{ cms_t('巴西为主', 'Mainly Brazil') }}</span></a>
            </div>
        </div>
    </section>

    @if($news->isNotEmpty())
        <section class="ig-section" style="padding-top:0">
            <div class="ig-wrap">
                <div class="ig-gallery-head">
                    <div>
                        <p class="ig-kicker">{{ cms_t('资讯', 'Insights') }}</p>
                        <h2>{{ cms_t('开站前看这几篇。', 'Read these before launch.') }}</h2>
                    </div>
                    <p><a class="ig-textlink" href="{{ $insightsUrl }}">{{ cms_t('全部', 'All articles') }}</a></p>
                </div>
                <div class="ig-news">
                    @foreach($news as $item)
                        <a href="{{ $item->url }}">
                            <time>@cmsDate(['name' => $item->published_at])</time>
                            <div>
                                <h3>{{ $item->t('title') }}</h3>
                                <p>{{ $item->t('excerpt') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <dialog class="ig-preview" id="ig-preview">
        <div class="ig-preview-bar">
            <strong class="ig-preview-title"></strong>
            <div class="ig-preview-tabs">
                <button type="button" data-pane="front" class="is-on">{{ cms_t('前台', 'Front') }}</button>
                <button type="button" data-pane="admin">{{ cms_t('后台', 'Admin') }}</button>
                <button type="button" data-pane="agent">{{ cms_t('代理', 'Agent') }}</button>
            </div>
            <button type="button" class="ig-preview-x" data-close>{{ cms_t('关闭', 'Close') }}</button>
        </div>
        <div class="ig-preview-body">
            <div data-pane-panel="front"></div>
            <div data-pane-panel="admin" hidden>
                <div class="ig-desk ig-desk-lg">
                    <div class="ig-desk-side">
                        <b>IG</b>
                        <span class="is-on">{{ cms_t('总览', 'Overview') }}</span>
                        <span>{{ cms_t('会员', 'Members') }}</span>
                        <span>{{ cms_t('注单', 'Bets') }}</span>
                        <span>{{ cms_t('财务', 'Finance') }}</span>
                        <span>{{ cms_t('活动', 'Promos') }}</span>
                        <span>{{ cms_t('皮肤', 'Templates') }}</span>
                    </div>
                    <div class="ig-desk-main">
                        <div class="ig-desk-top"><strong class="ig-pane-name"></strong><em class="ig-sample">{{ cms_t('界面示例', 'Sample screen') }}</em></div>
                        <div class="ig-desk-metrics">
                            <div><small>{{ cms_t('充值', 'Deposits') }}</small><b>{{ cms_t('86.4万', '864k') }}</b></div>
                            <div><small>{{ cms_t('提现', 'Withdrawals') }}</small><b>{{ cms_t('41.2万', '412k') }}</b></div>
                            <div><small>{{ cms_t('注单', 'Bets') }}</small><b>12,603</b></div>
                        </div>
                        <div class="ig-desk-table">
                            <div><span>14:21</span><span>{{ cms_t('会员 8821', 'Member 8821') }}</span><span>{{ cms_t('体育', 'Sports') }}</span><b>1.86</b></div>
                            <div><span>14:18</span><span>{{ cms_t('会员 1904', 'Member 1904') }}</span><span>{{ cms_t('真人', 'Live') }}</span><b>500</b></div>
                            <div><span>14:11</span><span>{{ cms_t('会员 6630', 'Member 6630') }}</span><span>{{ cms_t('电子', 'Slots') }}</span><b>200</b></div>
                            <div><span>14:02</span><span>{{ cms_t('会员 4418', 'Member 4418') }}</span><span>{{ cms_t('提现', 'Cash out') }}</span><b>8,000</b></div>
                        </div>
                    </div>
                </div>
            </div>
            <div data-pane-panel="agent" hidden>
                <div class="ig-agent">
                    <div class="ig-desk-top"><strong class="ig-pane-name"></strong><em class="ig-sample">{{ cms_t('界面示例', 'Sample screen') }}</em></div>
                    <div class="ig-desk-metrics">
                        <div><small>{{ cms_t('下级', 'Downline') }}</small><b>36</b></div>
                        <div><small>{{ cms_t('占成', 'Share') }}</small><b>42,800</b></div>
                        <div><small>{{ cms_t('待结算', 'Pending') }}</small><b>6,150</b></div>
                    </div>
                    <div class="ig-agent-link"><span>{{ cms_t('推广链接', 'Promo link') }}</span><em>ig.example/a/12</em></div>
                    <div class="ig-desk-table">
                        <div><span>A03</span><span>{{ cms_t('直属', 'Direct') }}</span><span>{{ cms_t('体育', 'Sports') }}</span><b>+1,200</b></div>
                        <div><span>A11</span><span>{{ cms_t('直属', 'Direct') }}</span><span>{{ cms_t('真人', 'Live') }}</span><b>+860</b></div>
                        <div><span>B02</span><span>{{ cms_t('二级', 'Level 2') }}</span><span>{{ cms_t('电子', 'Slots') }}</span><b>+240</b></div>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <section class="ig-section" id="objections">
        <div class="ig-wrap">
            <div class="ig-gallery-head">
                <div>
                    <p class="ig-kicker">{{ cms_t('先问清', 'Common questions') }}</p>
                    <h2>{{ cms_t('四件通常会问的事。', 'Four questions that come up.') }}</h2>
                </div>
                <p><a class="ig-textlink" href="{{ $faqUrl }}">{{ cms_t('全部问题', 'All questions') }}</a></p>
            </div>
            <div class="ig-asks">
                <div>
                    @include('themes.bc.partials.icon', ['name' => 'layers'])
                    <h3>{{ cms_t('和自己开发差在哪？', 'How is this different from building it?') }}</h3>
                    <p>{{ cms_t('包网用现成系统上线。自己开发要单独做前台、后台和接入，时间更长。', 'A white-label launch uses a system that already exists. Building it means a new front, admin, and connections, and takes longer.') }}</p>
                </div>
                <div>
                    @include('themes.bc.partials.icon', ['name' => 'split'])
                    <h3>{{ cms_t('标准和定制差在哪？', 'Standard or custom?') }}</h3>
                    <p>{{ cms_t('标准用现有模板，改品牌元素。定制改界面和流程，周期更长。天数不在这页上。', 'Standard keeps a current template and changes the brand. Custom changes the layout and the flow, and takes longer. Timelines are not on this page.') }}</p>
                </div>
                <div>
                    @include('themes.bc.partials.icon', ['name' => 'clip'])
                    <h3>{{ cms_t('上线要准备什么？', 'What do you need to start?') }}</h3>
                    <p>{{ cms_t('品牌名、Logo、对接人，以及目标市场。支付和游戏清单在方案里定。', 'Brand name, logo, a contact, and the market. Payments and the game list are set in the package.') }}</p>
                </div>
                <div>
                    @include('themes.bc.partials.icon', ['name' => 'tag'])
                    <h3>{{ cms_t('有公开报价吗？', 'Is there a public price?') }}</h3>
                    <p>{{ cms_t('没有。按档位和改动范围单独报。', 'No. It is quoted from the package and the scope of changes.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="ig-consult" id="contact">
        <div class="ig-wrap ig-consult-grid">
            <div class="ig-consult-copy">
                <p class="ig-kicker">{{ cms_t('咨询', 'Contact') }}</p>
                <h2>{{ cms_t('选定模板后留下联系方式。', 'Leave a contact once you pick a template.') }}</h2>
                <p class="ig-lead">{{ cms_t('写上想用的模板名，以及市场。报价和周期不在这页上。', 'Name the template and the market. Price and timing are not on this page.') }}</p>
                @php
                    $telegram = cms_theme('telegram');
                    $telegramHandle = '';
                    if (preg_match('~(?:t\.me/|@)([A-Za-z0-9_]{3,})~', $telegram, $telegramMatch)) {
                        $telegramHandle = '@'.$telegramMatch[1];
                    }
                @endphp
                @if($telegram !== '')
                    <a class="ig-tg" href="{{ $telegram }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 4.4 2.7 11.2c-1.3.5-1.2 1.2-.2 1.5l4.8 1.5 1.9 5.8c.2.7.1.9.8.9.5 0 .7-.2 1-.5l2.7-2.6 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.6c.3-1.2-.4-1.7-1.4-1.3z"/></svg><span>{{ $telegramHandle !== '' ? $telegramHandle : 'Telegram' }}</span></a>
                @endif
                <a class="ig-textlink" href="#templates">{{ cms_t('先看九套前台', 'See the templates first') }}</a>
            </div>
            <div class="ig-form">
                @cmsForm(['slug' => 'gw-inquiry', 'captcha' => true, 'submit' => cms_t('提交', 'Send')])
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-filter');
            document.querySelectorAll('[data-filter]').forEach(function (item) {
                item.classList.toggle('is-on', item === btn);
            });
            document.querySelectorAll('.ig-skins .skin').forEach(function (card) {
                card.hidden = key !== 'all' && card.getAttribute('data-kind') !== key;
            });
        });
    });

    var preview = document.getElementById('ig-preview');
    function showPane(name) {
        preview.querySelectorAll('[data-pane]').forEach(function (tab) {
            tab.classList.toggle('is-on', tab.getAttribute('data-pane') === name);
        });
        preview.querySelectorAll('[data-pane-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-pane-panel') !== name;
        });
    }
    function openPreview(card, pane) {
        var shot = card.querySelector('.skin-shot');
        var front = preview.querySelector('[data-pane-panel="front"]');
        var name = card.querySelector('h3').textContent;
        front.innerHTML = '';
        var clone = shot.cloneNode(true);
        clone.classList.add('is-hero');
        front.appendChild(clone);
        preview.querySelector('.ig-preview-title').textContent = name;
        preview.querySelectorAll('.ig-pane-name').forEach(function (node) {
            node.textContent = name;
        });
        showPane(pane || 'front');
        if (!preview.open) preview.showModal();
    }
    document.querySelectorAll('.ig-skins .skin').forEach(function (card) {
        card.addEventListener('click', function (event) {
            var end = event.target.closest('[data-open]');
            openPreview(card, end ? end.getAttribute('data-open') : 'front');
        });
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPreview(card, 'front');
            }
        });
    });
    preview.querySelectorAll('[data-pane]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            showPane(tab.getAttribute('data-pane'));
        });
    });
    preview.querySelector('[data-close]').addEventListener('click', function () {
        preview.close();
    });
</script>
@endpush
