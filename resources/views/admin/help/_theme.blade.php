@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">这里是主题里的 Blade 怎么写。在后台换哪一套、复制、上传，见「<a href="{{ $urls['helpThemes'] ?? '#' }}">主题</a>」。也可以在主题页点「编辑模板」直接改（自带主题请先复制一套）。取文章和栏目用 <code>@@cms*</code>，见「<a href="{{ $urls['tags'] ?? '#' }}">标签</a>」。不要在主题里写数据库查询。</p>

<h3>目录长什么样</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>resources/views/themes/enterprise/
  theme.json
  preview.png
  index/index.blade.php      首页（必须有，才能「用于本站」）
  list/list.blade.php        栏目列表
  list/list_product.blade.php 产品列表（可选）
  show/show.blade.php        文章详情
  show/show_product.blade.php 产品详情（可选）
  category/category.blade.php 栏目封面/频道页
  search.blade.php
  tag.blade.php
  sitemap.blade.php
  sitemap-page.blade.php   访客可读的站点地图（/sitemap）
  partials/layout.blade.php  公共头尾
  partials/paginate.blade.php
  member/login.blade.php     以及 register、profile 等</code></pre>
</div>
<p class="hint">缺某个文件时，会去企业主题或 <code>resources/views/cms/</code> 找一份凑上，不会整站空白。</p>

<h3>系统怎么选模板</h3>
<p class="muted">列表页：栏目自己的列表模板 → 模型上的 <code>list_tpl</code> → 默认 <code>list.list</code>。详情页：文档扩展字段 <code>show_tpl</code> → 栏目 → 模型 → 默认 <code>show.show</code>。名字按点号对应目录，例如 <code>list.list_product</code> 就是 <code>list/list_product.blade.php</code>。</p>
<p class="muted">布局优先 <code>partials/layout.blade.php</code>，没有再用主题根目录的 <code>layout.blade.php</code>。</p>

<h3>页面骨架</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
@verbatim
<pre><code>@extends('themes.enterprise.partials.layout')

@section('content')
    @cmsBreadcrumb
    &lt;h1&gt;{{ $category->t('title') }}&lt;/h1&gt;

    @cmsContent(['page' => true])
        &lt;a href="{{ $item->url }}"&gt;{{ $item->t('title') }}&lt;/a&gt;
    @endcmsContent

    @cmsPaginate
@endsection</code></pre>
@endverbatim
</div>
<p class="muted">公共头里放 <code>@@cmsSeo</code>。导航用 <code>@@include('cms.partials.header-nav')</code> 或自己写 <code>@@cmsMenu</code>。</p>

<h3>模板里能用的变量</h3>
<table class="help-doc">
    <thead>
        <tr><th>变量</th><th>什么时候有</th><th>常用写法</th></tr>
    </thead>
    <tbody>
        <tr><td><code>$site</code></td><td>几乎所有前台页</td><td>站名、Logo、主色等设置</td></tr>
        <tr><td><code>$category</code></td><td>栏目、列表</td><td><code>$category-&gt;t('title')</code>、<code>frontUrl()</code>、<code>page_size</code></td></tr>
        <tr><td><code>$entry</code></td><td>详情</td><td><code>$entry-&gt;t('title')</code>、<code>t('content')</code>、<code>url</code>、<code>cover</code>、<code>views</code></td></tr>
        <tr><td><code>$item</code></td><td>标签循环里</td><td>默认循环变量，嵌套时用 <code>'as' =&gt; 'cat'</code> 改名</td></tr>
    </tbody>
</table>
<p class="muted">多语言文案用 <code>t('title')</code>，不要直接 <code>-&gt;title</code>。模型扩展字段用 <code>$item-&gt;attr('price')</code>。图片地址用 <code>cms_media($item-&gt;cover)</code>，会走站点里配的 CDN。</p>

<h3>怎么加一套自己的</h3>
<ol class="help-steps">
    <li>{!! $hl('themes', '主题') !!}页「复制一套」，或上传 zip（包内要有 <code>index/index.blade.php</code>），或把文件夹直接放到 <code>themes/</code> 下。</li>
    <li>命令也可以：<code>php artisan cms:theme-make brand --from=enterprise --name=品牌站</code>。</li>
    <li>改 Blade 和 CSS。需要的模型、空栏目写在 <code>theme.json</code>，点「用于本站」时只补缺的，不改已有字段。</li>
    <li>预览图放 <code>preview.png</code>（或 jpg / svg）。</li>
</ol>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>{
  "name": "品牌站",
  "description": "公司官网",
  "i18n": true,
  "responsive": true,
  "models": ["article", "page", "product"],
  "config": {
    "home_notice": {"title": "首页公告", "type": "textarea", "value": ""}
  },
  "categories": [
    {"slug": "news", "title": {"zh_cn": "新闻中心", "en": "News"}, "model": "article", "type": "list", "menu": true}
  ]
}</code></pre>
</div>
<p class="hint">zip 里不要带可执行 PHP。自己主题的模型 JSON 也可放在 <code>models/标识.json</code>，格式和后台「导出模型」一样。栏目标题写成对象后，「用于本站」会按语言写入；公共头请留 <code>viewport</code>、语言切换和 <code>cms_ui()</code>。</p>

<h3>二次开发用事件，不要改核心</h3>
<p class="muted">内容保存、评论、表单、换主题、改设置会发出 Laravel 事件。在 <code>app/Providers/AppServiceProvider.php</code> 里 <code>Event::listen</code> 即可，不必改控制器。仓库 <code>CONTRIBUTING.md</code> 有同样一份表。</p>
<table class="help-doc">
    <thead>
        <tr><th>事件</th><th>什么时候</th></tr>
    </thead>
    <tbody>
        <tr><td><code>App\Events\EntrySaved</code></td><td>保存一篇内容。<code>$created</code> 是否新建；<code>$publishedNow</code> 是否这次变成已发布（会员投稿不会标成刚发布）</td></tr>
        <tr><td><code>App\Events\CommentSubmitted</code></td><td>前台提交评论（含待审）</td></tr>
        <tr><td><code>App\Events\CommentModerated</code></td><td>后台通过 / 拒绝 / 标垃圾</td></tr>
        <tr><td><code>App\Events\FormSubmitted</code></td><td>前台表单提交成功</td></tr>
        <tr><td><code>App\Events\ThemeActivated</code></td><td>当前站换了主题（<code>$slug</code> / <code>$previous</code>）</td></tr>
        <tr><td><code>App\Events\SettingSaved</code></td><td>设置写入（<code>$keys</code> 是改过的键）</td></tr>
    </tbody>
</table>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>use App\Events\EntrySaved;
use Illuminate\Support\Facades\Event;

Event::listen(EntrySaved::class, function (EntrySaved $e) {
    if ($e->publishedNow) {
        // 通知搜索引擎、写日志等
    }
});</code></pre>
</div>
<p class="hint">列表里批量改文章状态不发 <code>EntrySaved</code>，避免一次几百封邮件。不要为「发布时做什么」去改核心文件。</p>
