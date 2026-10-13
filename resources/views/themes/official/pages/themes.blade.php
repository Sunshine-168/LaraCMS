@extends('themes.official.partials.layout')

@section('title', cms_t('主题 — LeafCMS', 'Themes — LeafCMS'))
@section('description', cms_t('LeafCMS 自带企业、博客、下载、导航、源码、漫画、体育和移动主题。换主题只改变前台样子，已发布的内容会保留。', 'LeafCMS includes company, blog, download, directory, source, manga, sports, and mobile themes. Switching a theme keeps published entries.'))
@section('foot', 'LeafCMS · 换主题不丢已发布的内容')

@section('content')
    <div class="page-hero">
        <div class="wrap">
            <div class="kicker"><i></i> Themes</div>
            <h1>{{ cms_t('主题包决定站点形态', 'The theme decides the kind of site') }}</h1>
            <p class="lead">{{ cms_t('同一套后台可以换成不同的网站外观。切换主题不会清掉已经发布的文章，缺少的栏目会自动补上。', 'One admin can wear different skins. Switching does not delete published entries. Missing sections are added.') }}</p>
        </div>
    </div>
    <section>
        <div class="wrap themes">
            <article class="theme" id="enterprise"><div class="swatch" style="background:#14624e"></div><div class="theme-body"><h3>{{ cms_t('企业', 'Company') }}</h3><p>{{ cms_t('公司官网。适合发布新闻、公司介绍、产品和招聘信息。', 'Company site for news, about pages, products, and jobs.') }}</p><div class="pills"><span class="pill">article</span><span class="pill">page</span><span class="pill">product</span><span class="pill">job</span></div></div></article>
            <article class="theme" id="mobile"><div class="swatch" style="background:#1d6b6b"></div><div class="theme-body"><h3>{{ cms_t('移动', 'Mobile') }}</h3><p>{{ cms_t('手机上的阅读外观。可以按访问设备或域名自动切换。', 'A reading skin for phones. It can switch by device or domain.') }}</p><div class="pills"><span class="pill">mobile_theme</span></div></div></article>
            <article class="theme" id="blog"><div class="swatch" style="background:#8c4a2f"></div><div class="theme-body"><h3>{{ cms_t('博客', 'Blog') }}</h3><p>{{ cms_t('以文章为主，适合持续更新的内容站点。', 'Built around articles for a site that keeps publishing.') }}</p><div class="pills"><span class="pill">article</span></div></div></article>
            <article class="theme" id="download"><div class="swatch" style="background:#2f5d8c"></div><div class="theme-body"><h3>{{ cms_t('下载站', 'Downloads') }}</h3><p>{{ cms_t('用来整理资料，访客下载时会计入次数。', 'Files for visitors to download, with a count.') }}</p><div class="pills"><span class="pill">download</span></div></div></article>
            <article class="theme" id="nav"><div class="swatch" style="background:#3d6b4f"></div><div class="theme-body"><h3>{{ cms_t('导航', 'Directory') }}</h3><p>{{ cms_t('把常用网址按分类列出来，点击后会记下访问次数。', 'Links grouped by category. Clicks are counted.') }}</p><div class="pills"><span class="pill">link</span></div></div></article>
            <article class="theme" id="source"><div class="swatch" style="background:#5a4a8c"></div><div class="theme-body"><h3>{{ cms_t('源码', 'Source') }}</h3><p>{{ cms_t('用来展示和介绍项目，适合源码、案例这一类目录。', 'A catalog for projects, source packages, and cases.') }}</p><div class="pills"><span class="pill">source</span></div></div></article>
            <article class="theme" id="manga"><div class="swatch" style="background:#8c3d55"></div><div class="theme-body"><h3>{{ cms_t('漫画', 'Manga') }}</h3><p>{{ cms_t('作品和章节分开管理，适合连载内容。', 'Works and chapters are separate. Suited to serials.') }}</p><div class="pills"><span class="pill">manga</span><span class="pill">chapter</span></div></div></article>
            <article class="theme" id="sports"><div class="swatch" style="background:#2f6b4f"></div><div class="theme-body"><h3>{{ cms_t('体育', 'Sports') }}</h3><p>{{ cms_t('用来发布公开的赛事资讯。请只展示可以合法公开的信息。', 'Public match information. Only publish what you may legally show.') }}</p><div class="pills"><span class="pill">signal</span></div></div></article>
            <article class="theme" id="default"><div class="swatch" style="background:#6b6458"></div><div class="theme-body"><h3>{{ cms_t('默认', 'Default') }}</h3><p>{{ cms_t('页面不齐时用来补位。缺页时会回到企业主题的对应页面。', 'Fills in when a page is missing, then falls back to the company theme.') }}</p><div class="pills"><span class="pill">fallback</span></div></div></article>
        </div>
    </section>
    <section>
        <div class="wrap grid-2">
            <article class="card">
                <h3>{{ cms_t('自己加一套', 'Add your own') }}</h3>
                <p>{{ cms_t('可以在后台复制一套现成主题再改，也可以直接上传压缩包。命令行：', 'Copy a theme in the admin, or upload a zip. From the command line:') }}</p>
                <pre><code>php artisan cms:theme-make brand --from=enterprise --name=品牌站</code></pre>
            </article>
            <article class="card">
                <h3>theme.json</h3>
                <p>{{ cms_t('用来写主题名称、说明，以及它需要的内容类型和初始栏目。放一张预览图后，后台的主题卡片就会显示出来。', 'Name, description, models, and starter sections. A preview image shows on the admin theme card.') }}</p>
            </article>
        </div>
    </section>
@endsection
