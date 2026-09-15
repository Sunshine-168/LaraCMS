@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">The <strong>theme</strong> decides how the site looks. <strong>Content models</strong> decide fields on sections and entries. See <a href="{{ $urls['helpAdmin'] ?? '#' }}">Admin</a> for each sidebar item, <a href="{{ $urls['helpThemes'] ?? '#' }}">Themes</a> to switch skins, <a href="{{ $urls['theme'] ?? '#' }}">Templates</a> for Blade files, and <a href="{{ $urls['tags'] ?? '#' }}">Tags</a> for data tags.</p>

<div class="help-jumps">
    @if(!empty($urls['settings']))
        <a href="{{ $urls['settings'] }}">Site name</a>
    @endif
    @if(!empty($urls['themes']))
        <a href="{{ $urls['themes'] }}">Change theme</a>
    @endif
    @if(!empty($urls['categories']))
        <a href="{{ $urls['categories'] }}">Add a section</a>
    @endif
    @if(!empty($urls['entries']))
        <a href="{{ $urls['entries'] }}">Write an entry</a>
    @endif
    @if(!empty($urls['cache']))
        <a href="{{ $urls['cache'] }}">Clear cache</a>
    @endif
    <a href="{{ $urls['front'] ?? url('/') }}" target="_blank" rel="noopener">View site</a>
</div>

<h3>Suggested first steps</h3>
<ol class="help-steps">
    <li>In {!! $hl('settings', 'Settings') !!}, set the site name and a short intro.</li>
    <li>In {!! $hl('themes', 'Themes') !!}, pick a skin and click “Use on this site”. Missing models are added; existing entries are kept.</li>
    <li>In {!! $hl('categories', 'Sections') !!}, add channels (news, products, about). Each section needs a model so the list knows which fields to use.</li>
    <li>In {!! $hl('entries', 'Entries') !!}, write articles. People with publish rights can publish; editors save drafts or pending items by default.</li>
    <li>Open the front. If it still looks old after a theme change, clear {!! $hl('cache', 'Cache') !!}.</li>
</ol>
<p class="hint">If you installed demo data, sections and entries are already there — just edit the copy. On an empty site, start with sections. Missing sidebar items mean this account has no permission.</p>

<h3>What the sidebar is for</h3>
<table class="help-doc">
    <thead>
        <tr><th>Area</th><th>What you do</th></tr>
    </thead>
    <tbody>
        <tr><td>Content</td><td>{!! $hl('categories', 'Sections') !!}, {!! $hl('entries', 'entries') !!}, tags, media, inbox, comments, trash. More has flags, {!! $hl('friendLinks', 'friend links') !!}, {!! $hl('recommends', 'blocks') !!}, {!! $hl('models', 'models') !!}, {!! $hl('menus', 'menus') !!}, and {!! $hl('forms', 'forms') !!}.</td></tr>
        <tr><td>Operations</td><td>{!! $hl('members', 'Members') !!}, collectors, redirects, analytics, {!! $hl('cache', 'cache') !!} (page cache and disk HTML).</td></tr>
        <tr><td>System</td><td>{!! $hl('sites', 'Sites') !!}, {!! $hl('settings', 'Settings') !!}, {!! $hl('themes', 'themes') !!}, languages, logs, admins, roles, and this help.</td></tr>
    </tbody>
</table>

<h3>Sections and entries</h3>
<p class="muted">Common section types: a <strong>list</strong> of documents, a <strong>single</strong> page (about us), or an <strong>external link</strong>. An entry has one primary section (that sets the URL) and can also appear in extra sections.</p>
<p class="muted">The model sets the fields. Articles usually have title, cover, and body; products add price and similar. {!! $hl('models', 'Models') !!} can be copied, exported as JSON, and imported without deleting existing documents.</p>
<p class="muted">Scheduled publish: before the time, the front returns 404. Drafts can get a preview link that does not count views and is not cached.</p>

<h3>Menus, blocks, forms</h3>
<p class="muted">If a section is marked “menu”, and the theme’s <code>@@cmsMenu</code> has no menu yet, those sections become the header. For exact order, build a header menu in {!! $hl('menus', 'Menus') !!}.</p>
<p class="muted">Home banners and “latest news” blocks use {!! $hl('recommends', 'Blocks') !!}. The identifier must match the template <code>name</code>, for example <code>home_banner</code>.</p>
<p class="muted">Homepage friend links / partners are under More → {!! $hl('friendLinks', 'Friend links') !!}, stored as the <code>partners</code> block (or <code>friend_links</code>). That is not the nav theme’s “link” content model, which is a URL directory.</p>
<p class="muted">Build contact forms in {!! $hl('forms', 'Forms') !!} and output them with <code>@@cmsForm</code>. Submissions land in Inbox. Comments wait for approval by default; switches are in {!! $hl('settings', 'Settings') !!}.</p>

<h3>Members</h3>
<p class="muted">The front <code>/member</code> area handles register and login. If a model allows member posts and the group has that permission, members can submit drafts for review. Settings can turn comments and guest comments on.</p>

<h3>Sites, backups, cache</h3>
<p class="muted">{!! $hl('sites', 'Sites') !!} split by domain; sections and entries are isolated. Switch the site you are editing in the top bar. Backups are under {!! $hl('settingsMore', 'Settings → More') !!}, or <code>php artisan cms:backup</code>. Cache and static HTML are in {!! $hl('cache', 'Cache') !!}. nginx notes are under <a href="{{ $urls['env'] ?? '#' }}">Environment</a>.</p>

<h3>Common questions</h3>
<div class="help-faq">
    <details>
        <summary>I changed the theme, but the front still looks old</summary>
        <p>Page cache is still there. In {!! $hl('cache', 'Cache') !!} click “Clear cache”, or run <code>php artisan cms:html-clear</code>.</p>
    </details>
    <details>
        <summary>I wrote an entry but cannot see it</summary>
        <p>It must be <strong>published</strong> (not draft/pending), the publish time must have passed, and it must sit on the right section. Editors cannot publish by default — a super admin or someone with “Publish entries” must publish it.</p>
    </details>
    <details>
        <summary>Images or uploads 404</summary>
        <p>Run <code>php artisan storage:link</code> at the project root, and keep <code>storage</code> and <code>bootstrap/cache</code> writable. If you set a CDN, check the asset domain in Settings.</p>
    </details>
    <details>
        <summary>A block or form is empty</summary>
        <p>The admin identifier must match the template: block <code>name</code> (e.g. <code>home_banner</code>), form <code>slug</code> (e.g. <code>contact</code>). Disabled items or empty lists will not show.</p>
    </details>
    <details>
        <summary>I want the installer again</summary>
        <p>Delete <code>storage/app/install.lock</code> and open <code>/install</code>. Existing data is not wiped. Do not do this on a live site.</p>
    </details>
    <details>
        <summary>Live site shows a directory listing or 404</summary>
        <p>The web root must be the project’s <code>public</code> folder, not the project root. Full nginx / PHP / MySQL notes are under <a href="{{ $urls['env'] ?? '#' }}">Environment</a>.</p>
    </details>
</div>
