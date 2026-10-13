@extends('themes.official.partials.layout')

@section('title', cms_t('LeafCMS 官网 — Laravel 开源建站系统', 'LeafCMS — open-source Laravel site builder'))
@section('description', cms_t('LeafCMS 是可商用的开源建站系统，用来做企业官网、博客和多站点。内容在后台维护，前台用一套标签取出来。', 'LeafCMS is an open-source site builder for company sites, blogs, and multiple sites. Content is edited in the admin and pulled into themes with tags.'))

@section('content')
    <section class="hero">
        <div class="wrap hero-grid">
            <div>
                <div class="kicker"><i></i> Laravel 13 · MIT</div>
                <h1>{!! cms_t('用一套内核<br>做企业站和多站点', 'One core<br>for company sites and more') !!}</h1>
                <p class="lead">{{ cms_t('用来做企业官网、博客和多站点。新闻、产品、招聘和会员都在后台发布，前台页面用一套标签取出内容。会 HTML 就可以改外观。', 'Company sites, blogs, and multiple sites. News, products, jobs, and members are published in the admin. Themes pull that content with one set of tags. HTML is enough to change the look.') }}</p>
                <div class="actions">
                    <a class="btn btn-solid" href="{{ cms_route('site.guide') }}">{{ cms_t('三种安装方式', 'Three ways to install') }}</a>
                    <a class="btn btn-line" href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">GitHub</a>
                    <a class="btn btn-gold" href="{{ cms_route('site.docs') }}">{{ cms_t('系统说明', 'Docs') }}</a>
                </div>
                <p class="fine">{{ cms_t('需要 PHP 8.4 及以上。装好后就能打开网站前台和后台，上线前请先更换初始账号。', 'PHP 8.4 or newer. After install you can open the site and the admin. Change the initial account before going live.') }}</p>
            </div>
            <div class="preview" aria-hidden="true">
                <div class="preview-bar"><span class="dot"></span><span class="dot"></span><span class="dot"></span><span class="preview-url">leafcms / {{ cms_t('官网主题', 'official theme') }}</span></div>
                <div class="preview-body">
                    <aside class="preview-side">
                        <b>{{ cms_t('栏目', 'Menu') }}</b>
                        <span class="on">{{ cms_t('首页', 'Home') }}</span>
                        <span>{{ cms_t('新闻', 'News') }}</span>
                        <span>{{ cms_t('产品', 'Products') }}</span>
                        <span>{{ cms_t('关于', 'About') }}</span>
                        <span>{{ cms_t('招聘', 'Jobs') }}</span>
                    </aside>
                    <div class="preview-main">
                        <div class="pv-head">
                            <strong>{{ cms_t('首页', 'Home') }}</strong>
                            <span>{{ cms_t('栏目里的内容显示在这里', 'Section entries show up here') }}</span>
                        </div>
                        <div class="pv-grid">
                            <div class="pv-card"><em class="e1"></em><b>{{ cms_t('新闻', 'News') }}</b><i>{{ cms_t('企业动态', 'Updates') }}</i></div>
                            <div class="pv-card"><em class="e2"></em><b>{{ cms_t('产品', 'Products') }}</b><i>{{ cms_t('在售产品', 'Catalog') }}</i></div>
                            <div class="pv-card"><em class="e3"></em><b>{{ cms_t('关于', 'About') }}</b><i>{{ cms_t('公司介绍', 'Company') }}</i></div>
                            <div class="pv-card"><em class="e4"></em><b>{{ cms_t('招聘', 'Jobs') }}</b><i>{{ cms_t('在招岗位', 'Open roles') }}</i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="band">
        <div class="wrap facts">
            <div class="fact"><strong>PHP 8.4+</strong><span>{{ cms_t('一套程序即可建站，数据库可用 SQLite 或 MySQL', 'One program. SQLite or MySQL.') }}</span></div>
            <div class="fact"><strong>MIT</strong><span>{{ cms_t('开源可商用，不需要单独购买授权', 'Open source and commercial use. No separate license to buy.') }}</span></div>
            <div class="fact"><strong>{{ cms_t('9 套主题', '9 themes') }}</strong><span>{{ cms_t('企业站、博客、下载站和导航站可以随时切换', 'Company, blog, download, and directory skins can be switched any time.') }}</span></div>
            <div class="fact"><strong>@@cms*</strong><span>{{ cms_t('前台取栏目、文章和轮播，都用这一套标签', 'Sections, entries, and banners all use this one tag set.') }}</span></div>
        </div>
    </div>

    <section id="features">
        <div class="wrap">
            <div class="sec-head">
                <div>
                    <h2>{{ cms_t('建站时会用到的能力', 'What you use to build a site') }}</h2>
                    <p>{{ cms_t('发文章、换外观、开多个站点，这些都在同一套系统里完成。', 'Publishing, changing the skin, and running more than one site all happen in the same system.') }}</p>
                </div>
            </div>
            <div class="grid-3">
                <article class="card"><div class="ico"><svg viewBox="0 0 18 18" fill="none"><path d="M4 4h10M4 9h10M4 14h6" stroke="currentColor" stroke-width="1.6"/></svg></div><h3>{{ cms_t('模板标签', 'Template tags') }}</h3><p>{{ cms_t('导航、文章列表、轮播、分页和留言表单，都在页面里用标签调用，不用把数据查询写进模板。', 'Navigation, lists, banners, pagination, and forms are tags in the page. Themes do not query the database.') }}</p></article>
                <article class="card"><div class="ico"><svg viewBox="0 0 18 18" fill="none"><rect x="3" y="3" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M6 7h6M6 11h4" stroke="currentColor" stroke-width="1.6"/></svg></div><h3>{{ cms_t('内容模型', 'Content models') }}</h3><p>{{ cms_t('文章、产品、下载可以各自带不同字段。换成另一套主题后，已经发布的内容仍然保留。', 'Articles, products, and downloads can have different fields. Published entries stay when you switch themes.') }}</p></article>
                <article class="card"><div class="ico"><svg viewBox="0 0 18 18" fill="none"><path d="M3 14V7l6-4 6 4v7" stroke="currentColor" stroke-width="1.6"/><path d="M7.5 14v-4h3v4" stroke="currentColor" stroke-width="1.6"/></svg></div><h3>{{ cms_t('多站点', 'Multiple sites') }}</h3><p>{{ cms_t('多个域名共用这一套程序。每个站点的栏目、文章和会员分开管理，互不影响。', 'Several domains share one install. Each site keeps its own sections, entries, and members.') }}</p></article>
                <article class="card"><div class="ico"><svg viewBox="0 0 18 18" fill="none"><circle cx="9" cy="6.5" r="2.2" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 14.5c.6-2.2 2.3-3.3 4.5-3.3s3.9 1.1 4.5 3.3" stroke="currentColor" stroke-width="1.6"/></svg></div><h3>{{ cms_t('会员', 'Members') }}</h3><p>{{ cms_t('访客可以注册、登录、收藏和评论。会员提交的稿件需要审核通过后，才会出现在前台。', 'Visitors can register, sign in, save, and comment. Member submissions appear on the front only after review.') }}</p></article>
                <article class="card"><div class="ico"><svg viewBox="0 0 18 18" fill="none"><path d="M9 3v8M6 8.5 9 11.5 12 8.5" stroke="currentColor" stroke-width="1.6"/><path d="M4 14.5h10" stroke="currentColor" stroke-width="1.6"/></svg></div><h3>{{ cms_t('采集', 'Collect') }}</h3><p>{{ cms_t('可以把公开的资讯源按时间写入文章。同一条来源只入库一次，不会整站抓取。', 'Public feeds can be written in on a schedule. The same source item is stored once. It does not crawl a whole site.') }}</p></article>
                <article class="card"><div class="ico"><svg viewBox="0 0 18 18" fill="none"><path d="M4 5.5h10v8H4z" stroke="currentColor" stroke-width="1.6"/><path d="M7 5.5V4h4v1.5" stroke="currentColor" stroke-width="1.6"/></svg></div><h3>{{ cms_t('静态与缓存', 'Static files and cache') }}</h3><p>{{ cms_t('页面可以生成静态文件，也可以整页缓存。文章或栏目改过之后，对应页面会重新生成。', 'Pages can be written to static files or cached. After an entry or section changes, that page is built again.') }}</p></article>
            </div>
        </div>
    </section>

    <section id="cases">
        <div class="wrap">
            <div class="sec-head">
                <div>
                    <h2>{{ cms_t('两个已经上线的站', 'Two sites already online') }}</h2>
                    <p>{{ cms_t('截图来自线上首页，点图打开整站。', 'Screenshots are the live homepages. Open the image to visit the site.') }}</p>
                </div>
            </div>
            <div class="shots">
                <a class="shot" href="https://applecms.top/" target="_blank" rel="noopener">
                    <img src="{{ asset('brand/cases/applecms.png') }}" alt="苹果CMS 官网首页" width="1440" height="900">
                    <div class="shot-meta">
                        <h3>苹果CMS</h3>
                        <p>开源影视站。采集、播放、会员和插件都在这个站上。</p>
                        <span>applecms.top</span>
                    </div>
                </a>
                <a class="shot" href="https://xunqitong.im/" target="_blank" rel="noopener">
                    <img src="{{ asset('brand/cases/xunqitong.png') }}" alt="讯企通官网首页" width="1440" height="900">
                    <div class="shot-meta">
                        <h3>讯企通</h3>
                        <p>企业即时通讯。手机、桌面和后台在同一个官网里展示。</p>
                        <span>xunqitong.im</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="sec-head">
                <div>
                    <h2>{{ cms_t('换主题，就是另一种网站', 'Another theme, another kind of site') }}</h2>
                    <p>{{ cms_t('企业站、博客、下载站和导航站共用同一个后台。换主题只改变前台样子。', 'Company, blog, download, and directory themes share one admin. Switching a theme only changes the front.') }}</p>
                </div>
                <a class="btn btn-line" href="{{ cms_route('site.themes') }}">{{ cms_t('全部主题', 'All themes') }}</a>
            </div>
            <div class="cases">
                <a class="case" href="{{ cms_route('site.themes') }}#enterprise">
                    <div class="mini mini-ent" aria-hidden="true">
                        <div class="mini-bar"><b>{{ cms_t('企业官网', 'Company') }}</b><span>{{ cms_t('新闻', 'News') }}</span><span>{{ cms_t('产品', 'Work') }}</span><span>{{ cms_t('招聘', 'Jobs') }}</span></div>
                        <div class="mini-hero"></div>
                        <div class="mini-row"><span><i></i>{{ cms_t('方案', 'Plans') }}</span><span><i></i>{{ cms_t('案例', 'Cases') }}</span><span><i></i>{{ cms_t('新闻', 'News') }}</span></div>
                    </div>
                    <div class="case-body">
                        <h3>{{ cms_t('企业官网', 'Company site') }}</h3>
                        <p>{{ cms_t('新闻、产品、单页和招聘。适合公司介绍和持续更新的栏目。', 'News, products, pages, and jobs.') }}</p>
                    </div>
                </a>
                <a class="case" href="{{ cms_route('site.themes') }}#blog">
                    <div class="mini mini-blog" aria-hidden="true">
                        <div class="mini-bar"><b>{{ cms_t('资讯博客', 'Blog') }}</b><span>{{ cms_t('专栏', 'Column') }}</span><span>{{ cms_t('归档', 'Archive') }}</span></div>
                        <div class="mini-list">
                            <span><b>{{ cms_t('本周更新', 'This week') }}</b><i>{{ cms_t('列表', 'List') }}</i></span>
                            <span><b>{{ cms_t('栏目文章', 'Section') }}</b><i>{{ cms_t('列表', 'List') }}</i></span>
                            <span><b>{{ cms_t('阅读全文', 'Article') }}</b><i>{{ cms_t('详情', 'Detail') }}</i></span>
                        </div>
                    </div>
                    <div class="case-body">
                        <h3>{{ cms_t('资讯博客', 'Blog') }}</h3>
                        <p>{{ cms_t('栏目围绕文章展开，适合持续写内容、做列表和详情页。', 'Sections built around articles, lists, and detail pages.') }}</p>
                    </div>
                </a>
                <a class="case" href="{{ cms_route('site.themes') }}#download">
                    <div class="mini mini-down" aria-hidden="true">
                        <div class="mini-bar"><b>{{ cms_t('资料下载', 'Downloads') }}</b><span>{{ cms_t('文档', 'Docs') }}</span><span>{{ cms_t('安装包', 'Package') }}</span></div>
                        <div class="mini-files">
                            <span><i></i><b>{{ cms_t('说明书', 'Manual') }}</b><em>PDF</em></span>
                            <span><i></i><b>{{ cms_t('安装包', 'Package') }}</b><em>ZIP</em></span>
                        </div>
                    </div>
                    <div class="case-body">
                        <h3>{{ cms_t('资料下载', 'Downloads') }}</h3>
                        <p>{{ cms_t('提供文件下载，并记录每次下载。适合说明书、安装包和资料库。', 'File downloads with a count. Manuals, packages, and libraries.') }}</p>
                    </div>
                </a>
                <a class="case" href="{{ cms_route('site.themes') }}#nav">
                    <div class="mini mini-nav" aria-hidden="true">
                        <div class="mini-bar"><b>{{ cms_t('网址导航', 'Directory') }}</b><span>{{ cms_t('工具', 'Tools') }}</span><span>{{ cms_t('资源', 'Links') }}</span></div>
                        <div class="mini-links"><span>{{ cms_t('设计', 'Design') }}</span><span>{{ cms_t('开发', 'Dev') }}</span><span>{{ cms_t('文档', 'Docs') }}</span><span>{{ cms_t('社区', 'Community') }}</span></div>
                    </div>
                    <div class="case-body">
                        <h3>{{ cms_t('网址导航', 'Directory') }}</h3>
                        <p>{{ cms_t('把常用链接按栏目排开，适合工具站和资源入口。', 'Links grouped by section. Tool lists and resource hubs.') }}</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap grid-2">
            <div>
                <h2>{{ cms_t('装好就能打开前后台', 'Install, then open both sides') }}</h2>
                <p class="lead">{{ cms_t('可以用 Docker、命令行或网页向导安装。三条路装完，都能进入网站前台和后台。', 'Install with Docker, the command line, or the web wizard. All three open the site and the admin.') }}</p>
                <div class="steps">
                    <div class="step"><div class="num">1</div><div><strong>{{ cms_t('准备环境', 'Prepare') }}</strong><p class="note">{{ cms_t('准备 PHP 8.4 和 Composer。用 Docker 启动时，会一并准备好演示内容。', 'Have PHP 8.4 and Composer. Docker also loads the demo content on first start.') }}</p></div></div>
                    <div class="step"><div class="num">2</div><div><strong>{{ cms_t('执行安装', 'Install') }}</strong><p class="note">{{ cms_t('命令行执行安装即可。如果希望先看到示例栏目和文章，安装时带上演示数据。', 'Run the installer. Add the demo flag if you want sample sections and entries.') }}</p></div></div>
                    <div class="step"><div class="num">3</div><div><strong>{{ cms_t('进入站点', 'Open the site') }}</strong><p class="note">{{ cms_t('前台是访客看到的网站，后台用来发内容和改设置。站点对外发布前，请更换初始账号。', 'The front is what visitors see. The admin is where you publish and change settings. Replace the initial account before going live.') }}</p></div></div>
                </div>
                <p><a class="btn btn-solid" href="{{ cms_route('site.guide') }}">{{ cms_t('查看安装命令', 'Install commands') }}</a></p>
            </div>
            <pre><code>docker compose up --build

# 或命令行
composer install
php artisan key:generate
php artisan cms:install --demo
php artisan serve</code></pre>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="limit">
                <div>
                    <h2>{{ cms_t('扩展不用换系统', 'Extend it without replacing it') }}</h2>
                    <p>{{ cms_t('新的外观是一套主题目录，可以在后台复制现有主题，也可以上传压缩包。前台始终用同一套标签取内容。', 'A new look is a theme folder. Copy one in the admin or upload a zip. The front keeps using the same tags.') }}</p>
                </div>
                <ul>
                    <li>{{ cms_t('栏目和文章在模板里用标签取出', 'Sections and entries come from tags in the theme') }}</li>
                    <li>{{ cms_t('页面仍然是普通 HTML，方便改版', 'Pages stay ordinary HTML') }}</li>
                    <li>{{ cms_t('交流在', 'Talk in the') }} <a href="https://t.me/applecms" target="_blank" rel="noopener">{{ cms_t('飞机群', 'Telegram group') }}</a></li>
                    <li>{{ cms_t('联系', 'Contact') }} <a href="https://t.me/sanpang" target="_blank" rel="noopener">{{ cms_t('飞机号', 'Telegram') }} @sanpang</a></li>
                </ul>
            </div>
        </div>
    </section>
@endsection
