@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
    $copy = admin_t('guide.copy');
@endphp
<p class="muted">How to write Blade inside a theme. Switching, copying, and uploading skins is under <a href="{{ $urls['helpThemes'] ?? '#' }}">Themes</a>. Pull entries with <code>@@cms*</code> — see <a href="{{ $urls['tags'] ?? '#' }}">Tags</a>. Do not query the database from a theme.</p>

<h3>Folder layout</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>resources/views/themes/enterprise/
  theme.json
  preview.png
  index/index.blade.php      home (required for “Use on this site”)
  list/list.blade.php        section list
  list/list_product.blade.php product list (optional)
  show/show.blade.php        entry
  show/show_product.blade.php product (optional)
  category/category.blade.php section cover
  search.blade.php
  tag.blade.php
  sitemap.blade.php
  partials/layout.blade.php  chrome
  partials/paginate.blade.php
  member/login.blade.php     plus register, profile, …</code></pre>
</div>
<p class="hint">If a file is missing, the enterprise theme or <code>resources/views/cms/</code> is used so the site is not blank.</p>

<h3>How a template is chosen</h3>
<p class="muted">List: section list template → model <code>list_tpl</code> → default <code>list.list</code>. Show: entry <code>show_tpl</code> → section → model → default <code>show.show</code>. Dots map to folders, so <code>list.list_product</code> is <code>list/list_product.blade.php</code>.</p>
<p class="muted">Layout prefers <code>partials/layout.blade.php</code>, then a root <code>layout.blade.php</code>.</p>

<h3>Page skeleton</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
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
<p class="muted">Put <code>@@cmsSeo</code> in the head. Nav: <code>@@include('cms.partials.header-nav')</code> or <code>@@cmsMenu</code>.</p>

<h3>Variables</h3>
<table class="help-doc">
    <thead>
        <tr><th>Variable</th><th>When</th><th>Typical use</th></tr>
    </thead>
    <tbody>
        <tr><td><code>$site</code></td><td>Almost every front page</td><td>Name, logo, color</td></tr>
        <tr><td><code>$category</code></td><td>Section, list</td><td><code>$category-&gt;t('title')</code>, <code>frontUrl()</code>, <code>page_size</code></td></tr>
        <tr><td><code>$entry</code></td><td>Show</td><td><code>$entry-&gt;t('title')</code>, <code>t('content')</code>, <code>url</code>, <code>cover</code>, <code>views</code></td></tr>
        <tr><td><code>$item</code></td><td>Inside a tag loop</td><td>Default loop var; nest with <code>'as' =&gt; 'cat'</code></td></tr>
    </tbody>
</table>
<p class="muted">Use <code>t('title')</code> for locales, not <code>-&gt;title</code>. Extra fields: <code>$item-&gt;attr('price')</code>. Images: <code>cms_media($item-&gt;cover)</code> (honors CDN).</p>

<h3>Make your own</h3>
<ol class="help-steps">
    <li>On {!! $hl('themes', 'Themes') !!} click “Copy one”, upload a zip with <code>index/index.blade.php</code>, or drop a folder under <code>themes/</code>.</li>
    <li>Or: <code>php artisan cms:theme-make brand --from=enterprise --name=Brand</code>.</li>
    <li>Edit Blade and CSS. Models and empty sections go in <code>theme.json</code>. “Use on this site” only fills gaps.</li>
    <li>Preview: <code>preview.png</code> (or jpg / svg).</li>
</ol>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>{
  "name": "Brand",
  "description": "Company site",
  "i18n": true,
  "responsive": true,
  "models": ["article", "page", "product"],
  "categories": [
    {"slug": "news", "title": {"zh_cn": "新闻中心", "en": "News"}, "model": "article", "type": "list", "menu": true}
  ]
}</code></pre>
</div>
<p class="hint">No executable PHP in the zip. Custom model JSON can live in <code>models/slug.json</code> (same as “Export model”). Object titles are written per language on apply. Keep viewport, the language switcher, and <code>cms_ui()</code> in the layout.</p>
