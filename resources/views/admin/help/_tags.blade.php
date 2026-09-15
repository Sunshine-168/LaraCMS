@php
    $urls = $urls ?? [];
@endphp
<p class="muted">前台只用 <code>@@cms*</code>。循环类标签要成对写结束指令。循环变量默认 <code>$item</code>，嵌套时必须用 <code>'as' =&gt; '别名'</code>。换哪套外观见「<a href="{{ $urls['helpThemes'] ?? '#' }}">主题</a>」，文件放哪见「<a href="{{ $urls['theme'] ?? '#' }}">模板</a>」。</p>

<h3>常用例子</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
@verbatim
<pre><code>@cmsChannel(['type' => 'top', 'ismenu' => 1])
    &lt;a href="{{ $item->frontUrl() }}"&gt;{{ $item->t('title') }}&lt;/a&gt;
@endcmsChannel

@cmsContent(['model' => 'article', 'num' => 6])
    &lt;a href="{{ $item->url }}"&gt;{{ $item->t('title') }}&lt;/a&gt;
@endcmsContent

@cmsAdv(['name' => 'home_banner'])
    &lt;img src="{{ $item->image }}" alt="{{ $item->t('title') }}"&gt;
@endcmsAdv</code></pre>
@endverbatim
</div>

<h3>栏目列表页</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
@verbatim
<pre><code>@cmsContent(['page' => true, 'num' => $category->page_size ?: 10])
    &lt;article&gt;
        &lt;div&gt;@cmsDate(['name' => $item->published_at])&lt;/div&gt;
        &lt;h3&gt;&lt;a href="{{ $item->url }}"&gt;{{ $item->t('title') }}&lt;/a&gt;&lt;/h3&gt;
        &lt;p&gt;@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 100])&lt;/p&gt;
    &lt;/article&gt;
@endcmsContent
@cmsPaginate</code></pre>
@endverbatim
</div>

<h3>详情页</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
@verbatim
<pre><code>@cmsBreadcrumb
&lt;h1&gt;{{ $entry->t('title') }}&lt;/h1&gt;
&lt;div&gt;{!! $entry->linked_content ?? $entry->t('content') !!}&lt;/div&gt;
&lt;p&gt;上一篇：@cmsPrev 下一篇：@cmsNext&lt;/p&gt;</code></pre>
@endverbatim
</div>

<h3>嵌套循环（必须改名）</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
@verbatim
<pre><code>@cmsChannel(['type' => 'top', 'as' => 'cat'])
    &lt;h2&gt;{{ $cat->t('title') }}&lt;/h2&gt;
    @cmsContent(['catid' => $cat->id, 'num' => 4, 'as' => 'row'])
        &lt;a href="{{ $row->url }}"&gt;{{ $row->t('title') }}&lt;/a&gt;
    @endcmsContent
@endcmsChannel</code></pre>
@endverbatim
</div>

<h3>指令一览</h3>
<table class="help-doc">
    <thead>
        <tr><th>指令</th><th>作用</th><th>常用参数</th></tr>
    </thead>
    <tbody>
        <tr><td><code>@@cmsSeo</code></td><td>输出 title / keywords / description</td><td>不用参数，放在 <code>&lt;head&gt;</code></td></tr>
        <tr><td><code>@@cmsChannel</code></td><td>栏目循环</td><td><code>type=top|son|peer|selftop</code>，<code>ismenu</code>，<code>num</code>，<code>name</code>（栏目 id 或 slug）</td></tr>
        <tr><td><code>@@cmsMenu</code></td><td>后台导航；没有菜单时回退栏目</td><td><code>location=header</code>（默认）</td></tr>
        <tr><td><code>@@cmsContent</code></td><td>文档列表</td><td><code>catid</code>，<code>model</code>，<code>flag</code>，<code>num</code>，<code>page</code>，<code>order</code>，<code>insub</code>，<code>related</code> + <code>related_field</code></td></tr>
        <tr><td><code>@@cmsPaginate</code></td><td>分页（配合 <code>page=&gt;true</code>）</td><td>可传入分页视图名</td></tr>
        <tr><td><code>@@cmsArcone</code></td><td>指定一篇</td><td><code>aid</code> 或 <code>id</code></td></tr>
        <tr><td><code>@@cmsAdv</code></td><td>推荐位</td><td><code>name</code> 必填；<code>num</code>；HTML 条目输出 <code>$item-&gt;html</code></td></tr>
        <tr><td><code>@@cmsBreadcrumb</code></td><td>面包屑</td><td><code>symbol</code>，<code>class</code></td></tr>
        <tr><td><code>@@cmsPrev</code> / <code>@@cmsNext</code></td><td>同栏目上下篇</td><td><code>field</code>，<code>msg</code>（没有时的文字）</td></tr>
        <tr><td><code>@@cmsDate</code></td><td>格式化日期</td><td><code>name</code> 或 <code>value</code>，<code>format=Y-m-d</code></td></tr>
        <tr><td><code>@@cmsSubstr</code></td><td>截断纯文本</td><td><code>name</code>，<code>len=80</code>，<code>dot</code></td></tr>
        <tr><td><code>@@cmsFilter</code></td><td>模型筛选字段</td><td><code>model</code>；配合地址栏 <code>?filter[字段]=值</code></td></tr>
        <tr><td><code>@@cmsOrder</code></td><td>排序链接</td><td>输出 <code>$item-&gt;url</code> / <code>label</code> / <code>current</code></td></tr>
        <tr><td><code>@@cmsFileinfo</code></td><td>附件信息</td><td><code>field</code> 或 <code>name</code>，<code>aid</code></td></tr>
        <tr><td><code>@@cmsForm</code></td><td>整份表单（含验证码）</td><td><code>slug</code>，<code>captcha</code>，<code>submit</code></td></tr>
        <tr><td><code>@@cmsFormFields</code></td><td>只循环字段，自己画表单</td><td><code>slug</code></td></tr>
        <tr><td><code>@@cmsTaglist</code></td><td>标签云</td><td><code>num=20</code></td></tr>
        <tr><td><code>@@cmsMap</code></td><td>嵌入 OpenStreetMap</td><td><code>point=经度,纬度</code>，<code>address</code>，<code>width</code>，<code>height</code></td></tr>
    </tbody>
</table>

<h3>@@cmsContent 再说明</h3>
<p class="muted">不写 <code>catid</code> 时，列表页默认当前栏目（含子栏目，<code>insub=&gt;false</code> 可关掉）。<code>catid=&gt;0</code> 或 <code>'all'</code> 不限栏目。<code>model</code> 可以是标识（如 <code>article</code>）或模型 id。<code>flag</code> 对推荐属性。<code>related</code> 按扩展字段找关联文档，例如漫画章节：<code>related=&gt;$entry-&gt;id, related_field=&gt;'manga_id'</code>。</p>

<h3>辅助函数</h3>
<table class="help-doc">
    <thead>
        <tr><th>函数</th><th>作用</th></tr>
    </thead>
    <tbody>
        <tr><td><code>cms_media($path)</code></td><td>附件地址，走 CDN 设置</td></tr>
        <tr><td><code>cms_route('download', $entry)</code></td><td>带语言前缀的命名路由</td></tr>
        <tr><td><code>cms_front_url('news')</code></td><td>前台路径</td></tr>
        <tr><td><code>cms_ui('search')</code></td><td>主题短句，在「语言 → 主题文案」里改。搜索、登录、评论、翻页、上一篇、404 都走这里。可写 <code>cms_ui('hello', ['name' => $name])</code> 替换 <code>:name</code>。</td></tr>
        <tr><td><code>cms_t('中文', 'English')</code></td><td>按当前内容语言选一句。只有中英时够用，新语言请改用 <code>cms_ui()</code>。</td></tr>
    </tbody>
</table>
<p class="hint">正文 HTML 不要用两个花括号（会转义标签），要用 Blade 的 raw 输出：<code>@verbatim{!! $entry->t('content') !!}@endverbatim</code>。不要在主题里新写一套标签，也不要引用别的 CMS 指令。</p>
