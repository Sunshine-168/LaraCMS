@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">This walks through every admin screen. Each module also has a one-line hint at the top. Start with <a href="{{ route('admin.help') }}">Getting started</a>; skins are under <a href="{{ $urls['helpThemes'] ?? '#' }}">Themes</a>.</p>

<h3>Overview</h3>
<table class="help-doc">
    <thead>
        <tr><th>Screen</th><th>What it does</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('dashboard', 'Dashboard') !!}</td><td>Pending entries, unread inbox, comments to review. Shortcut tiles can be edited on the dashboard.</td></tr>
        <tr><td>{!! $hl('stats', 'Analytics') !!}</td><td>Front PV / UV / IP, popular pages, referrers, and spider hits. Admin paths are not counted.</td></tr>
    </tbody>
</table>

<h3>Content</h3>
<table class="help-doc">
    <thead>
        <tr><th>Screen</th><th>What it does</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('entries', 'Entries') !!}</td><td>Write articles. “Publish entries” is required to publish; editors save drafts or pending items. Supports scheduled publish, preview links, and restoring the previous draft. One primary section sets the URL; extra sections are optional.</td></tr>
        <tr><td>{!! $hl('categories', 'Sections') !!}</td><td>Channel tree. List, single page, or external link. Bind a model. You can set list/show templates.</td></tr>
        <tr><td>{!! $hl('entryTags', 'Tags') !!}</td><td>Tags on entries; the front aggregates at <code>/tag/slug</code>. Not the same as Help → Tags (template directives).</td></tr>
        <tr><td>{!! $hl('media', 'Media') !!}</td><td>Upload images and files. Body images and block covers are picked here too. If files 404, run <code>php artisan storage:link</code>. To change only the public host, set CDN under {!! $hl('settingsMore', 'Settings → More') !!}. To store files on OSS / S3, set <code>CMS_MEDIA_DISK</code> — see <a href="{{ $urls['env'] ?? '#' }}">Environment</a>. Watermarks run on the local disk only.</td></tr>
        <tr><td>{!! $hl('submissions', 'Inbox') !!}</td><td>Form submissions. Mark read, add notes, reply by email.</td></tr>
        <tr><td>{!! $hl('comments', 'Comments') !!}</td><td>Pending by default; approved comments show under the entry. Switches and notify email are in {!! $hl('settings', 'Settings') !!}.</td></tr>
        <tr><td>{!! $hl('recycle', 'Trash') !!}</td><td>Deleted entries land here. Restore, or delete forever. In the sidebar it sits under Content → More; the entries list also has a button.</td></tr>
        <tr><td>{!! $hl('flags', 'Flags') !!}</td><td>Checkboxes on entries (e.g. headline). Filter with <code>@@cmsContent(['flag' =&gt; 'flag-slug'])</code>.</td></tr>
        <tr><td>{!! $hl('recommends', 'Blocks') !!}</td><td>Home banners and similar. The identifier must match <code>@@cmsAdv(['name' =&gt; 'home_banner'])</code>. Fill items by hand or pull entries by rule. Hand-filled items can pick an image from the media library and preview it before save. Friend links are under Operations → More.</td></tr>
        <tr><td>{!! $hl('models', 'Models') !!}</td><td>Fields for a content type (price, files, …). Copy or export/import JSON without deleting entries. “Allow member posts” lets members write this type.</td></tr>
        <tr><td>{!! $hl('menus', 'Menus') !!}</td><td>Header menus. Location such as <code>header</code> must match <code>@@cmsMenu</code>. If none exist, sections marked “menu” are used.</td></tr>
        <tr><td>{!! $hl('forms', 'Forms') !!}</td><td>Contact form fields. Slug goes to <code>@@cmsForm(['slug' =&gt; 'contact'])</code>. Submissions go to Inbox.</td></tr>
    </tbody>
</table>

<h3>Operations</h3>
<table class="help-doc">
    <thead>
        <tr><th>Screen</th><th>What it does</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('members', 'Members') !!}</td><td>Front registrants. Can be disabled. Posts and favorites live in the member area.</td></tr>
        <tr><td>{!! $hl('collectors', 'Collectors') !!}</td><td>Pull public RSS, JSON, or HTML lists into a section. Test first, then enable. Only sources you may use; no intranet crawling. Cron is required for auto runs — see <a href="{{ $urls['schedule'] ?? '#' }}">Schedule</a>.</td></tr>
        <tr><td>{!! $hl('cache', 'Cache') !!}</td><td>Full-page cache and disk HTML under <code>public/html/</code>. Top-right “Clear cache” clears views/config and stored pages. nginx is under <a href="{{ $urls['env'] ?? '#' }}">Environment</a>.</td></tr>
        <tr><td>{!! $hl('friendLinks', 'Friend links') !!}</td><td>Homepage friend links / partners — under Operations → More. Not the nav theme’s URL directory. Stored as the <code>partners</code> block (or <code>friend_links</code>). Themes output them with <code>@@cmsAdv(['name' =&gt; 'partners'])</code>.</td></tr>
        <tr><td>{!! $hl('sitemap', 'Sitemap') !!}</td><td>URL list for search engines — under Operations → More. XML at <code>/sitemap.xml</code>, HTML at <code>/sitemap</code>. Toggle sections / entries / tags, and add extra URLs. Member-gated entries and unused tags are skipped. <code>robots.txt</code> lists every language’s XML.</td></tr>
        <tr><td>{!! $hl('redirects', 'Redirects') !!}</td><td>301/302 from old paths. Per site, with hit counts. Disabling keeps the rule but stops jumping.</td></tr>
        <tr><td>{!! $hl('groups', 'Groups') !!}</td><td>Member capabilities (e.g. contribute). Disabling a group turns off those front rights; people stay in the list.</td></tr>
    </tbody>
</table>

<h3>System</h3>
<table class="help-doc">
    <thead>
        <tr><th>Screen</th><th>What it does</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('sites', 'Sites') !!}</td><td>One site per domain; content is isolated. Switch in the top bar. New sites copy settings, not content. The default site cannot be deleted; deleting a site deletes its content.</td></tr>
        <tr><td>{!! $hl('settings', 'Settings') !!}</td><td>Site name and keywords. Look: logo, color, footer. Comment rules and SMTP. Admins can reset passwords on the login page. More: URL format, extra robots.txt rules, mobile theme, backup, API token, CDN. The object-storage disk is not in this form — see <a href="{{ $urls['env'] ?? '#' }}">Environment</a>.</td></tr>
        <tr><td>{!! $hl('themes', 'Themes') !!}</td><td>Change the front skin. “Use on this site” adds missing models and does not edit existing entries. Details in <a href="{{ $urls['helpThemes'] ?? '#' }}">Themes</a>.</td></tr>
        <tr><td>{!! $hl('locales', 'Languages') !!}</td><td>Three separate things: <strong>content languages</strong> (entry fields, /en prefix), <strong>admin UI</strong> (sidebar EN/ZH), <strong>theme phrases</strong> (Search, Log in, Comments — <code>cms_ui('search')</code>). Menus and blocks get inputs per language. Coverage only counts titles actually filled. Disabling a language does not delete translations. Saving languages or theme phrases clears page cache and disk HTML.</td></tr>
        <tr><td>{!! $hl('logs', 'Logs') !!}</td><td>What admins did: which entry, bulk actions, logins and IP. IPs are labeled local, Docker LAN, or public region. Click an IP to filter. Static-page progress pings are not logged. This is not a revision history.</td></tr>
        <tr><td>{!! $hl('profile', 'Account') !!}</td><td>Click your name in the top right. Change display name and password.</td></tr>
        <tr><td>{!! $hl('adminUsers', 'Admins') !!}</td><td>Admin accounts, roles, which sites they may edit. Can limit to certain sections.</td></tr>
        <tr><td>{!! $hl('roles', 'Roles') !!}</td><td>Module permissions. Super admin can do everything and cannot be deleted. “Publish entries” is separate — without it, publish is blocked.</td></tr>
        <tr><td>Help</td><td>This page. Every account can open it, so it stays at the bottom of System instead of Operations → More.</td></tr>
    </tbody>
</table>
<p class="hint">A missing sidebar item is a permission gap, not a missing feature. Template tags are under <a href="{{ $urls['tags'] ?? '#' }}">Tags</a>.</p>
