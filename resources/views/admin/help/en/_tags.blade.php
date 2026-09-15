@php
    $urls = $urls ?? [];
    $copy = admin_t('guide.copy');
@endphp
<p class="muted">The front only uses <code>@@cms*</code>. Loop tags need a matching end tag. The loop variable is <code>$item</code>; nested loops must set <code>'as' =&gt; 'alias'</code>. Skins: <a href="{{ $urls['helpThemes'] ?? '#' }}">Themes</a>. Files: <a href="{{ $urls['theme'] ?? '#' }}">Templates</a>.</p>

<h3>Typical examples</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
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

<h3>Section list</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
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

<h3>Show page</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
@verbatim
<pre><code>@cmsBreadcrumb
&lt;h1&gt;{{ $entry->t('title') }}&lt;/h1&gt;
&lt;div&gt;{!! $entry->linked_content ?? $entry->t('content') !!}&lt;/div&gt;
&lt;p&gt;Previous: @cmsPrev Next: @cmsNext&lt;/p&gt;</code></pre>
@endverbatim
</div>

<h3>Nested loops (rename required)</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
@verbatim
<pre><code>@cmsChannel(['type' => 'top', 'as' => 'cat'])
    &lt;h2&gt;{{ $cat->t('title') }}&lt;/h2&gt;
    @cmsContent(['catid' => $cat->id, 'num' => 4, 'as' => 'row'])
        &lt;a href="{{ $row->url }}"&gt;{{ $row->t('title') }}&lt;/a&gt;
    @endcmsContent
@endcmsChannel</code></pre>
@endverbatim
</div>

<h3>Directive list</h3>
<table class="help-doc">
    <thead>
        <tr><th>Directive</th><th>Role</th><th>Common args</th></tr>
    </thead>
    <tbody>
        <tr><td><code>@@cmsSeo</code></td><td>title / keywords / description</td><td>No args; put in <code>&lt;head&gt;</code></td></tr>
        <tr><td><code>@@cmsChannel</code></td><td>Section loop</td><td><code>type=top|son|peer|selftop</code>, <code>ismenu</code>, <code>num</code>, <code>name</code> (id or slug)</td></tr>
        <tr><td><code>@@cmsMenu</code></td><td>Admin menu; falls back to sections</td><td><code>location=header</code> (default)</td></tr>
        <tr><td><code>@@cmsContent</code></td><td>Entry list</td><td><code>catid</code>, <code>model</code>, <code>flag</code>, <code>num</code>, <code>page</code>, <code>order</code>, <code>insub</code>, <code>related</code> + <code>related_field</code></td></tr>
        <tr><td><code>@@cmsPaginate</code></td><td>Pager (with <code>page=&gt;true</code>)</td><td>Optional view name</td></tr>
        <tr><td><code>@@cmsArcone</code></td><td>One entry</td><td><code>aid</code> or <code>id</code></td></tr>
        <tr><td><code>@@cmsAdv</code></td><td>Block</td><td><code>name</code> required; <code>num</code>; HTML items in <code>$item-&gt;html</code>. Friend links use <code>partners</code> or <code>friend_links</code></td></tr>
        <tr><td><code>@@cmsBreadcrumb</code></td><td>Breadcrumb</td><td><code>symbol</code>, <code>class</code></td></tr>
        <tr><td><code>@@cmsPrev</code> / <code>@@cmsNext</code></td><td>Prev/next in the same section</td><td><code>field</code>, <code>msg</code></td></tr>
        <tr><td><code>@@cmsDate</code></td><td>Format a date</td><td><code>name</code> or <code>value</code>, <code>format=Y-m-d</code></td></tr>
        <tr><td><code>@@cmsSubstr</code></td><td>Plain-text clip</td><td><code>name</code>, <code>len=80</code>, <code>dot</code></td></tr>
        <tr><td><code>@@cmsFilter</code></td><td>Model filters</td><td><code>model</code>; query <code>?filter[field]=value</code></td></tr>
        <tr><td><code>@@cmsOrder</code></td><td>Sort links</td><td><code>$item-&gt;url</code> / <code>label</code> / <code>current</code></td></tr>
        <tr><td><code>@@cmsFileinfo</code></td><td>Attachment info</td><td><code>field</code> or <code>name</code>, <code>aid</code></td></tr>
        <tr><td><code>@@cmsForm</code></td><td>Full form (captcha included)</td><td><code>slug</code>, <code>captcha</code>, <code>submit</code></td></tr>
        <tr><td><code>@@cmsFormFields</code></td><td>Field loop only</td><td><code>slug</code></td></tr>
        <tr><td><code>@@cmsTaglist</code></td><td>Tag cloud</td><td><code>num=20</code></td></tr>
        <tr><td><code>@@cmsMap</code></td><td>OpenStreetMap embed</td><td><code>point=lng,lat</code>, <code>address</code>, <code>width</code>, <code>height</code></td></tr>
    </tbody>
</table>

<h3>More on @@cmsContent</h3>
<p class="muted">With no <code>catid</code>, a list page uses the current section (including children; <code>insub=&gt;false</code> turns that off). <code>catid=&gt;0</code> or <code>'all'</code> means any section. <code>model</code> can be a slug (<code>article</code>) or id. <code>flag</code> filters flags. <code>related</code> finds related docs, e.g. manga chapters: <code>related=&gt;$entry-&gt;id, related_field=&gt;'manga_id'</code>.</p>

<h3>Helpers</h3>
<table class="help-doc">
    <thead>
        <tr><th>Function</th><th>Role</th></tr>
    </thead>
    <tbody>
        <tr><td><code>cms_media($path)</code></td><td>Asset URL, with CDN</td></tr>
        <tr><td><code>cms_route('download', $entry)</code></td><td>Named route with locale prefix</td></tr>
        <tr><td><code>cms_front_url('news')</code></td><td>Front path</td></tr>
        <tr><td><code>cms_ui('search')</code></td><td>Theme phrase from Languages → Theme phrases. Search, login, comments, pager, prev/next, 404. <code>cms_ui('hello', ['name' => $name])</code> replaces <code>:name</code>.</td></tr>
        <tr><td><code>cms_t('中文', 'English')</code></td><td>Pick a string by content locale. Fine for two languages; use <code>cms_ui()</code> for more.</td></tr>
    </tbody>
</table>
<p class="hint">Do not print HTML body with two curly braces (it will escape). Use Blade raw: <code>@verbatim{!! $entry->t('content') !!}@endverbatim</code>. Do not invent a second tag dialect or copy another CMS’s directives.</p>
