<!DOCTYPE html>
<html lang="{{ \App\Support\AdminUi::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '后台') - CMS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.staticfile.net/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) ?: '1' }}">
    @stack('styles')
</head>
<body>
@php
    $admin = auth('admin')->user();
    $can = fn (string $key) => $admin?->canPerm($key);
    $switchSites = $admin ? \App\Support\AdminSiteAccess::manageableSites($admin) : collect();
    $currentSite = \App\Support\CurrentSite::model();
    $headerInbox = $admin
        ? app(\App\Services\Admin\DashboardService::class)->headerInbox($admin)
        : ['total' => 0, 'hint' => '', 'entries' => 0, 'forms' => 0, 'comments' => 0];
    $contentMoreOpen = request()->routeIs('admin.flags.*', 'admin.recommends.*', 'admin.models.*', 'admin.menus.*', 'admin.forms.*', 'admin.recycle.*');
    $showContentMore = $can('flags') || $can('recommends') || $can('models') || $can('menus') || $can('forms') || $can('recycle');
    $opsMoreOpen = request()->routeIs('admin.friend-links.*', 'admin.sitemap.*', 'admin.redirects.*', 'admin.member-groups.*');
    $showOpsPrimary = $can('members') || $can('collectors') || $can('html_cache');
    $showOpsMore = $can('friend_links') || $can('sitemap') || $can('redirects') || $can('member_groups');
    $foldOpsMore = $showOpsPrimary && $showOpsMore;
@endphp
<div class="shell">
    <div class="side-backdrop" id="sideBackdrop"></div>
    <aside class="side" id="adminSide">
        <div class="side-brand">
            <span class="logo">C</span>
            <span>{{ admin_t('brand') }}</span>
        </div>
        <nav class="side-nav">
            @if($can('dashboard') || $can('stats'))
                <div class="nav-header">{{ admin_t('nav.overview') }}</div>
                @if($can('dashboard'))
                    <x-side-link :href="route('admin.dashboard')" icon="tachometer-alt" :label="admin_t('nav.dashboard')" :active="request()->routeIs('admin.dashboard*')" />
                @endif
                @if($can('stats'))
                    <x-side-link :href="route('admin.stats.index')" icon="chart-line" :label="admin_t('nav.stats')" :active="request()->routeIs('admin.stats.*')" />
                @endif
            @endif

            @if($can('entries') || $can('recycle') || $can('categories') || $can('tags') || $can('flags') || $can('recommends') || $can('models') || $can('media') || $can('menus') || $can('forms') || $can('form_submissions') || $can('comments'))
                <div class="nav-header">{{ admin_t('nav.content') }}</div>
                @if($can('entries'))
                    <x-side-link :href="route('admin.entries.index')" icon="file-alt" :label="admin_t('nav.entries')" :active="request()->routeIs('admin.entries.*')" :count="$headerInbox['entries'] ?? 0" />
                @endif
                @if($can('categories'))
                    <x-side-link :href="route('admin.categories.index')" icon="sitemap" :label="admin_t('nav.categories')" :active="request()->routeIs('admin.categories.*')" />
                @endif
                @if($can('tags'))
                    <x-side-link :href="route('admin.tags.index')" icon="tags" :label="admin_t('nav.tags')" :active="request()->routeIs('admin.tags.*')" />
                @endif
                @if($can('media'))
                    <x-side-link :href="route('admin.media.index')" icon="photo-video" :label="admin_t('nav.media')" :active="request()->routeIs('admin.media.*')" />
                @endif
                @if($can('form_submissions'))
                    <x-side-link :href="route('admin.form-submissions.index')" icon="inbox" :label="admin_t('nav.inbox')" :active="request()->routeIs('admin.form-submissions.*')" :count="$headerInbox['forms'] ?? 0" />
                @endif
                @if($can('comments'))
                    <x-side-link :href="route('admin.comments.index')" icon="comments" :label="admin_t('nav.comments')" :active="request()->routeIs('admin.comments.*')" :count="$headerInbox['comments'] ?? 0" />
                @endif
                @if($showContentMore)
                    <details class="nav-fold{{ $contentMoreOpen ? ' is-open' : '' }}" @if($contentMoreOpen) open @endif>
                        <summary>{{ admin_t('nav.more') }}</summary>
                        <div class="side-sub">
                            @if($can('flags'))
                                <x-side-link :href="route('admin.flags.index')" icon="flag" :label="admin_t('nav.flags')" :active="request()->routeIs('admin.flags.*')" />
                            @endif
                            @if($can('recommends'))
                                <x-side-link :href="route('admin.recommends.index')" icon="th-large" :label="admin_t('nav.recommends')" :active="request()->routeIs('admin.recommends.*')" />
                            @endif
                            @if($can('models'))
                                <x-side-link :href="route('admin.models.index')" icon="cube" :label="admin_t('nav.models')" :active="request()->routeIs('admin.models.*')" />
                            @endif
                            @if($can('menus'))
                                <x-side-link :href="route('admin.menus.index')" icon="bars" :label="admin_t('nav.menus')" :active="request()->routeIs('admin.menus.*')" />
                            @endif
                            @if($can('forms'))
                                <x-side-link :href="route('admin.forms.index')" icon="clipboard-list" :label="admin_t('nav.forms')" :active="request()->routeIs('admin.forms.*')" />
                            @endif
                            @if($can('recycle'))
                                <x-side-link :href="route('admin.recycle.index')" icon="trash-alt" :label="admin_t('nav.recycle')" :active="request()->routeIs('admin.recycle.*')" />
                            @endif
                        </div>
                    </details>
                @endif
            @endif

            @if($showOpsPrimary || $showOpsMore)
                <div class="nav-header">{{ admin_t('nav.ops') }}</div>
                @if($can('members'))
                    <x-side-link :href="route('admin.members.index')" icon="users" :label="admin_t('nav.members')" :active="request()->routeIs('admin.members.*')" />
                @endif
                @if($can('collectors'))
                    <x-side-link :href="route('admin.collectors.index')" icon="cloud-download-alt" :label="admin_t('nav.collectors')" :active="request()->routeIs('admin.collectors.*')" />
                @endif
                @if($can('html_cache'))
                    <x-side-link :href="route('admin.html-cache.edit')" icon="bolt" :label="admin_t('nav.cache')" :active="request()->routeIs('admin.html-cache.*')" />
                @endif
                @if($foldOpsMore)
                    <details class="nav-fold{{ $opsMoreOpen ? ' is-open' : '' }}" @if($opsMoreOpen) open @endif>
                        <summary>{{ admin_t('nav.more') }}</summary>
                        <div class="side-sub">
                @endif
                            @if($can('friend_links'))
                                <x-side-link :href="route('admin.friend-links.index')" icon="link" :label="admin_t('nav.friend_links')" :active="request()->routeIs('admin.friend-links.*')" />
                            @endif
                            @if($can('sitemap'))
                                <x-side-link :href="route('admin.sitemap.edit')" icon="map" :label="admin_t('nav.sitemap')" :active="request()->routeIs('admin.sitemap.*')" />
                            @endif
                            @if($can('redirects'))
                                <x-side-link :href="route('admin.redirects.index')" icon="exchange-alt" :label="admin_t('nav.redirects')" :active="request()->routeIs('admin.redirects.*')" />
                            @endif
                            @if($can('member_groups'))
                                <x-side-link :href="route('admin.member-groups.index')" icon="user-tag" :label="admin_t('nav.groups')" :active="request()->routeIs('admin.member-groups.*')" />
                            @endif
                @if($foldOpsMore)
                        </div>
                    </details>
                @endif
            @endif

            <div class="nav-header">{{ admin_t('nav.system') }}</div>
            @if($can('sites'))
                <x-side-link :href="route('admin.sites.index')" icon="globe" :label="admin_t('nav.sites')" :active="request()->routeIs('admin.sites.*')" />
            @endif
            @if($can('settings'))
                <x-side-link :href="route('admin.settings.edit')" icon="cog" :label="admin_t('nav.settings')" :active="request()->routeIs('admin.settings.*')" />
                <x-side-link :href="route('admin.themes.index')" icon="palette" :label="admin_t('nav.themes')" :active="request()->routeIs('admin.themes.*')" />
            @endif
            @if($can('locales'))
                <x-side-link :href="route('admin.locales.index')" icon="language" :label="admin_t('nav.locales')" :active="request()->routeIs('admin.locales.*')" />
            @endif
            @if($can('logs'))
                <x-side-link :href="route('admin.logs.index')" icon="history" :label="admin_t('nav.logs')" :active="request()->routeIs('admin.logs.*')" />
            @endif
            @if($can('admin_users'))
                <x-side-link :href="route('admin.admin-users.index')" icon="user-cog" :label="admin_t('nav.admins')" :active="request()->routeIs('admin.admin-users.*')" />
            @endif
            @if($can('roles'))
                <x-side-link :href="route('admin.roles.index')" icon="user-shield" :label="admin_t('nav.roles')" :active="request()->routeIs('admin.roles.*')" />
            @endif
            <x-side-link :href="route('admin.help')" icon="book" :label="admin_t('nav.help')" :active="request()->routeIs('admin.help')" />
        </nav>
        <div class="side-foot">
            <span class="side-foot-avatar">{{ mb_substr($admin?->name ?? 'A', 0, 1) }}</span>
            <span class="side-foot-meta">
                <strong>{{ $admin?->name ?? admin_t('top.admin') }}</strong>
                <em>{{ $admin?->adminRole?->name ?? admin_t('top.no_role') }}</em>
            </span>
        </div>
    </aside>

    <div class="main-wrap">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="btn btn-muted btn-sm topbar-menu" id="toggleSide" title="{{ admin_t('top.menu') }}"><i class="fas fa-bars"></i></button>
                <h1 class="topbar-title">@yield('title', admin_t('brand'))</h1>
                @if($headerInbox['total'] > 0)
                    <a class="topbar-todo" href="{{ route('admin.dashboard') }}#dash-todos" title="{{ $headerInbox['hint'] }}">{{ admin_t('top.todo') }} {{ $headerInbox['total'] }}</a>
                @endif
            </div>
            <div class="topbar-right">
                @if($switchSites->count() > 1)
                    <form method="post" action="{{ route('admin.site.switch') }}" class="site-switch">
                        @csrf
                        <label for="top-site">{{ admin_t('top.site') }}</label>
                        <select id="top-site" name="site_id" onchange="this.form.submit()">
                            @foreach($switchSites as $s)
                                <option value="{{ $s->id }}" @selected((int)($currentSite?->id) === (int)$s->id)>
                                    {{ $s->name }}{{ $s->is_default ? '（'.admin_t('top.default').'）' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <form method="post" action="{{ route('admin.ui-locale') }}" class="ui-switch">
                    @csrf
                    @foreach(\App\Support\AdminUi::options() as $code => $uiLabel)
                        <button type="submit" name="ui_locale" value="{{ $code }}" class="{{ \App\Support\AdminUi::current() === $code ? 'is-on' : '' }}">{{ $uiLabel }}</button>
                    @endforeach
                </form>
                <a class="btn btn-sm topbar-front" href="{{ url('/') }}" target="_blank" rel="noopener">{{ admin_t('top.front') }}</a>
                @if($headerInbox['total'] > 0)
                    <a class="topbar-bell" href="{{ route('admin.dashboard') }}#dash-todos" title="{{ $headerInbox['hint'] }}">
                        <i class="fas fa-inbox"></i>
                        <span class="topbar-bell-count">{{ $headerInbox['total'] }}</span>
                    </a>
                @endif
                <details class="account-menu">
                    <summary class="user-chip">
                        <span class="avatar">{{ mb_substr($admin?->name ?? 'A', 0, 1) }}</span>
                        <span class="user-chip-name">{{ $admin?->name }}</span>
                    </summary>
                    <div class="account-menu-panel">
                        <p class="muted">{{ $admin?->adminRole?->name ?? admin_t('top.admin') }}</p>
                        <a class="account-menu-link" href="{{ route('admin.profile.edit') }}">{{ admin_t('top.password') }}</a>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="btn btn-muted btn-sm" type="submit">{{ admin_t('top.logout') }}</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main class="content">
            @if(session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="flash error">{{ session('error') }}</div>
            @endif

            @hasSection('fullwidth')
                @yield('content')
            @else
                @php $pageIcon = trim($__env->yieldContent('icon')) ?: 'list'; @endphp
                <div class="card card-panel">
                    <div class="card-header">
                        <span><i class="fas fa-{{ $pageIcon }}"></i> @yield('title', admin_t('brand'))</span>
                        <div>@yield('header_actions')</div>
                    </div>
                    <div class="card-body">
                        @yield('content')
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>
<script>
    (function () {
        var side = document.getElementById('adminSide');
        var backdrop = document.getElementById('sideBackdrop');
        function setOpen(open) {
            side?.classList.toggle('open', open);
            backdrop?.classList.toggle('show', open);
            document.body.classList.toggle('side-open', open);
        }
        document.getElementById('toggleSide')?.addEventListener('click', function () {
            setOpen(!side?.classList.contains('open'));
        });
        backdrop?.addEventListener('click', function () { setOpen(false); });
        side?.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { setOpen(false); });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
