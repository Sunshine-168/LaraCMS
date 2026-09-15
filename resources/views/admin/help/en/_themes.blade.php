@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">A theme is a front skin (Blade files + the models it declares). There is no app store — switch them on this machine in {!! $hl('themes', 'Themes') !!}. Files and tags: <a href="{{ $urls['theme'] ?? '#' }}">Templates</a>.</p>

<div class="help-jumps">
    @if(!empty($urls['themes']))
        <a href="{{ $urls['themes'] }}">Open themes</a>
    @endif
    @if(!empty($urls['cache']))
        <a href="{{ $urls['cache'] }}">Clear cache</a>
    @endif
    <a href="{{ $urls['theme'] ?? '#' }}">Edit templates</a>
    <a href="{{ $urls['front'] ?? url('/') }}" target="_blank" rel="noopener">View site</a>
</div>

<h3>Use a theme on this site</h3>
<ol class="help-steps">
    <li>Open {!! $hl('themes', 'Themes') !!}. “Current” is the one in use. “Configure” opens that skin’s phrases, models, and custom options.</li>
    <li>Click the preview. Themes without a home page cannot be used (no <code>index/index.blade.php</code>).</li>
    <li>Click “Use on this site”: that skin becomes current, and <strong>missing models</strong> and <strong>empty sections</strong> are added. Existing fields, sections, and entries are not changed or deleted.</li>
    <li>If this skin is already current but models are missing, the button is “Add missing models” — it does not switch skins.</li>
    <li>If the front is stale, clear {!! $hl('cache', 'Cache') !!}.</li>
</ol>
<p class="hint">Empty sections have no entries yet — write them. The sports theme is a public listing demo; use it lawfully.</p>

<h3>Add a theme</h3>
<table class="help-doc">
    <thead>
        <tr><th>How</th><th>Notes</th></tr>
    </thead>
    <tbody>
        <tr><td>Copy one</td><td>Copy a complete theme folder. Only edit the new directory; bundled themes stay untouched.</td></tr>
        <tr><td>Upload zip</td><td>Must contain <code>index/index.blade.php</code>. A top folder name can be the slug. No executable PHP.</td></tr>
        <tr><td>Drop a folder</td><td>Put it in <code>resources/views/themes/slug/</code> and refresh the themes page.</td></tr>
        <tr><td>CLI</td><td><code>php artisan cms:theme-make brand --from=enterprise --name=Brand</code></td></tr>
    </tbody>
</table>
<p class="muted">Bundled themes cannot be removed. Custom ones can, which deletes the folder. The current theme cannot be removed. Slugs are lowercase letters, numbers, and hyphens.</p>

<h3>Bundled themes</h3>
<table class="help-doc">
    <thead>
        <tr><th>Theme</th><th>Best for</th><th>Models</th></tr>
    </thead>
    <tbody>
        <tr><td>Enterprise</td><td>Company site</td><td>Article, page, product, job</td></tr>
        <tr><td>Blog</td><td>Simple blog</td><td>Article</td></tr>
        <tr><td>Download</td><td>File downloads</td><td>Download</td></tr>
        <tr><td>Nav</td><td>Link directory</td><td>Link</td></tr>
        <tr><td>Source</td><td>Source shop</td><td>Source</td></tr>
        <tr><td>Manga</td><td>Comics</td><td>Manga, chapter</td></tr>
        <tr><td>Sports</td><td>Listings / signals</td><td>Signal</td></tr>
        <tr><td>Mobile</td><td>Phone skin</td><td>Similar to enterprise</td></tr>
    </tbody>
</table>
<p class="muted">“Use on this site” fills gaps from <code>theme.json</code> <code>models</code> / <code>categories</code> (the Configure page can override which models). Section titles may be <code>{"zh_cn":"新闻中心","en":"News"}</code>. Multilingual / responsive can also be toggled per skin. Custom options live in <code>config</code> and are read with <code>cms_theme('key')</code>.</p>

<h3>Name, logo, mobile skin</h3>
<p class="muted">Change the site name in {!! $hl('settings', 'Settings') !!}. {!! $hl('settingsLook', 'Settings → Look') !!} uploads logo / favicon, color, footer, ICP. Empty values keep the theme defaults.</p>
<p class="muted">A separate phone skin: under Settings → More pick “Mobile theme” (often <code>mobile</code>), optionally a mobile domain, or switch by user-agent.</p>

<h3>Theme FAQ</h3>
<div class="help-faq">
    <details>
        <summary>Will “Use on this site” wipe my entries?</summary>
        <p>No. It only adds <strong>missing</strong> models and empty sections. Existing fields, sections, and entries stay. After a skin change, some fields may not show until you align models or templates.</p>
    </details>
    <details>
        <summary>The button is grey / “missing home”</summary>
        <p>There is no <code>index/index.blade.php</code>. Incomplete copy or zip causes this. Bundled themes should not.</p>
    </details>
    <details>
        <summary>Front did not change</summary>
        <p>Clear {!! $hl('cache', 'Cache') !!}. If disk HTML is on, regenerate it.</p>
    </details>
    <details>
        <summary>What to edit after copying</summary>
        <p>Only Blade / CSS / <code>theme.json</code> in the new folder. Refresh the themes page. Put a preview in <code>preview.png</code>. Tag syntax is under <a href="{{ $urls['tags'] ?? '#' }}">Tags</a>.</p>
    </details>
</div>
