@extends('themes.official.partials.layout')

@section('title', cms_t('开源 — LeafCMS', 'Open source — LeafCMS'))
@section('description', cms_t('LeafCMS 源码在 GitHub：https://github.com/Sunshine-168/LeafCMS ，MIT 许可。', 'LeafCMS source is on GitHub: https://github.com/Sunshine-168/LeafCMS . MIT licensed.'))

@section('content')
    <div class="page-hero">
        <div class="wrap">
            <div class="kicker"><i></i> MIT</div>
            <h1>{{ cms_t('源码在 GitHub', 'Source on GitHub') }}</h1>
            <p class="lead">{!! cms_t('开源仓库是 <strong>LeafCMS</strong>：<code>https://github.com/Sunshine-168/LeafCMS</code>', 'The public repo is <strong>LeafCMS</strong>: <code>https://github.com/Sunshine-168/LeafCMS</code>') !!}</p>
            <div class="actions">
                <a class="btn btn-solid" href="https://github.com/Sunshine-168/LeafCMS" target="_blank" rel="noopener">{{ cms_t('打开 GitHub', 'Open GitHub') }}</a>
                <a class="btn btn-line" href="https://github.com/Sunshine-168/LeafCMS/archive/refs/heads/main.zip">{{ cms_t('下载 zip', 'Download zip') }}</a>
                <a class="btn btn-gold" href="{{ cms_route('site.guide') }}">{{ cms_t('安装说明', 'Install') }}</a>
            </div>
        </div>
    </div>
    <section>
        <div class="wrap grid-2">
            <article class="card">
                <h3>LeafCMS</h3>
                <p>{{ cms_t('产品源码。MIT 许可，默认分支 main。', 'Product source. MIT license. Default branch is main.') }}</p>
                <pre><code>git clone https://github.com/Sunshine-168/LeafCMS.git
cd LeafCMS</code></pre>
            </article>
            <article class="card">
                <h3>{{ cms_t('拉下来之后', 'After you clone') }}</h3>
                <p>{{ cms_t('没有 Git 就下载 zip，解压到能看到 artisan 的那一层。然后按安装页的 Docker、命令行或网页向导装。改前台看说明里的模板和标签。', 'No Git? Download the zip and unpack it where you can see artisan. Then use Docker, the command line, or the web wizard on the install page. Theme and tag notes are in Docs.') }}</p>
                <p class="note"><a href="{{ cms_route('site.guide') }}">{{ cms_t('安装', 'Install') }}</a> · <a href="{{ cms_route('site.docs') }}">{{ cms_t('说明', 'Docs') }}</a> · <a href="https://github.com/Sunshine-168" target="_blank" rel="noopener">Sunshine-168</a></p>
            </article>
            <article class="card">
                <h3>{{ cms_t('许可', 'License') }}</h3>
                <p>{{ cms_t('MIT，可以商用。二次开发按 Laravel 常规做：业务放在 app/Services，外观放在 resources/views/themes/。不会做在线升级、应用市场、OAuth 或支付。', 'MIT, including commercial use. Extend it the Laravel way: business code in app/Services, skins in resources/views/themes/. There is no online upgrade, marketplace, OAuth, or payments.') }}</p>
            </article>
            <article class="card">
                <h3>{{ cms_t('中文和英文', 'Chinese and English') }}</h3>
                <p>{{ cms_t('本站右上角可以切换简体中文和 English。后台说明也有两套文案，官网「说明」用的就是那一套。', 'This site switches between 简体中文 and English. The admin guide has both languages, and Docs on this site uses those same pages.') }}</p>
            </article>
        </div>
    </section>
@endsection
