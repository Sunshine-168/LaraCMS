<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '后台') - CMS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
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
    $contentMoreOpen = request()->routeIs('admin.flags.*', 'admin.recommends.*', 'admin.models.*', 'admin.menus.*', 'admin.forms.*');
    $showContentMore = $can('flags') || $can('recommends') || $can('models') || $can('menus') || $can('forms');
@endphp
<div class="shell">
    <div class="side-backdrop" id="sideBackdrop"></div>
    <aside class="side" id="adminSide">
        <div class="side-brand">
            <span class="logo">C</span>
            <span>CMS 后台</span>
        </div>
        <nav class="side-nav">
            @if($can('dashboard') || $can('stats'))
                <div class="nav-header">概览</div>
                @if($can('dashboard'))
                    <x-side-link :href="route('admin.dashboard')" icon="tachometer-alt" label="仪表盘" :active="request()->routeIs('admin.dashboard*')" />
                @endif
                @if($can('stats'))
                    <x-side-link :href="route('admin.stats.index')" icon="chart-line" label="统计" :active="request()->routeIs('admin.stats.*')" />
                @endif
            @endif

            @if($can('entries') || $can('recycle') || $can('categories') || $can('tags') || $can('flags') || $can('recommends') || $can('models') || $can('media') || $can('menus') || $can('forms') || $can('form_submissions') || $can('comments'))
                <div class="nav-header">内容</div>
                @if($can('entries'))
                    <x-side-link :href="route('admin.entries.index')" icon="file-alt" label="内容" :active="request()->routeIs('admin.entries.*')" :count="$headerInbox['entries'] ?? 0" />
                @endif
                @if($can('categories'))
                    <x-side-link :href="route('admin.categories.index')" icon="sitemap" label="栏目" :active="request()->routeIs('admin.categories.*')" />
                @endif
                @if($can('tags'))
                    <x-side-link :href="route('admin.tags.index')" icon="tags" label="标签" :active="request()->routeIs('admin.tags.*')" />
                @endif
                @if($can('media'))
                    <x-side-link :href="route('admin.media.index')" icon="photo-video" label="媒体" :active="request()->routeIs('admin.media.*')" />
                @endif
                @if($can('form_submissions'))
                    <x-side-link :href="route('admin.form-submissions.index')" icon="inbox" label="留言" :active="request()->routeIs('admin.form-submissions.*')" :count="$headerInbox['forms'] ?? 0" />
                @endif
                @if($can('comments'))
                    <x-side-link :href="route('admin.comments.index')" icon="comments" label="评论" :active="request()->routeIs('admin.comments.*')" :count="$headerInbox['comments'] ?? 0" />
                @endif
                @if($can('recycle'))
                    <x-side-link :href="route('admin.recycle.index')" icon="trash-alt" label="回收站" :active="request()->routeIs('admin.recycle.*')" />
                @endif
                @if($showContentMore)
                    <details class="nav-fold{{ $contentMoreOpen ? ' is-open' : '' }}" @if($contentMoreOpen) open @endif>
                        <summary>更多</summary>
                        <div class="side-sub">
                            @if($can('flags'))
                                <x-side-link :href="route('admin.flags.index')" icon="flag" label="推荐" :active="request()->routeIs('admin.flags.*')" />
                            @endif
                            @if($can('recommends'))
                                <x-side-link :href="route('admin.recommends.index')" icon="th-large" label="推荐位" :active="request()->routeIs('admin.recommends.*')" />
                            @endif
                            @if($can('models'))
                                <x-side-link :href="route('admin.models.index')" icon="cube" label="模型" :active="request()->routeIs('admin.models.*')" />
                            @endif
                            @if($can('menus'))
                                <x-side-link :href="route('admin.menus.index')" icon="bars" label="导航" :active="request()->routeIs('admin.menus.*')" />
                            @endif
                            @if($can('forms'))
                                <x-side-link :href="route('admin.forms.index')" icon="clipboard-list" label="表单" :active="request()->routeIs('admin.forms.*')" />
                            @endif
                        </div>
                    </details>
                @endif
            @endif

            @if($can('collectors') || $can('members') || $can('member_groups') || $can('html_cache') || $can('redirects'))
                <div class="nav-header">运营</div>
                @if($can('redirects'))
                    <x-side-link :href="route('admin.redirects.index')" icon="exchange-alt" label="跳转" :active="request()->routeIs('admin.redirects.*')" />
                @endif
                @if($can('collectors'))
                    <x-side-link :href="route('admin.collectors.index')" icon="cloud-download-alt" label="采集" :active="request()->routeIs('admin.collectors.*')" />
                @endif
                @if($can('members'))
                    <x-side-link :href="route('admin.members.index')" icon="users" label="会员" :active="request()->routeIs('admin.members.*')" />
                @endif
                @if($can('member_groups'))
                    <x-side-link :href="route('admin.member-groups.index')" icon="user-tag" label="分组" :active="request()->routeIs('admin.member-groups.*')" />
                @endif
                @if($can('html_cache'))
                    <x-side-link :href="route('admin.html-cache.edit')" icon="bolt" label="缓存" :active="request()->routeIs('admin.html-cache.*')" />
                @endif
            @endif

            @if($can('sites') || $can('settings') || $can('logs') || $can('admin_users') || $can('roles') || $can('locales'))
                <div class="nav-header">系统</div>
                @if($can('sites'))
                    <x-side-link :href="route('admin.sites.index')" icon="globe" label="站点" :active="request()->routeIs('admin.sites.*')" />
                @endif
                @if($can('settings'))
                    <x-side-link :href="route('admin.settings.edit')" icon="cog" label="设置" :active="request()->routeIs('admin.settings.*')" />
                    <x-side-link :href="route('admin.cache.edit')" icon="tools" label="工具" :active="request()->routeIs('admin.cache.*')" />
                @endif
                @if($can('locales'))
                    <x-side-link :href="route('admin.locales.index')" icon="language" label="语言" :active="request()->routeIs('admin.locales.*')" />
                @endif
                @if($can('logs'))
                    <x-side-link :href="route('admin.logs.index')" icon="history" label="日志" :active="request()->routeIs('admin.logs.*')" />
                @endif
                @if($can('admin_users'))
                    <x-side-link :href="route('admin.admin-users.index')" icon="user-cog" label="管理员" :active="request()->routeIs('admin.admin-users.*')" />
                @endif
                @if($can('roles'))
                    <x-side-link :href="route('admin.roles.index')" icon="user-shield" label="权限" :active="request()->routeIs('admin.roles.*')" />
                @endif
            @endif
        </nav>
        <div class="side-foot">
            <span class="side-foot-avatar">{{ mb_substr($admin?->name ?? 'A', 0, 1) }}</span>
            <span class="side-foot-meta">
                <strong>{{ $admin?->name ?? '管理员' }}</strong>
                <em>{{ $admin?->adminRole?->name ?? '未分角色' }}</em>
            </span>
        </div>
    </aside>

    <div class="main-wrap">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="btn btn-muted btn-sm topbar-menu" id="toggleSide" title="菜单"><i class="fas fa-bars"></i></button>
                <h1 class="topbar-title">@yield('title', '后台')</h1>
                @if($headerInbox['total'] > 0)
                    <a class="topbar-todo" href="{{ route('admin.dashboard') }}#dash-todos" title="{{ $headerInbox['hint'] }}">待办 {{ $headerInbox['total'] }}</a>
                @endif
            </div>
            <div class="topbar-right">
                @if($switchSites->count() > 1)
                    <form method="post" action="{{ route('admin.site.switch') }}" class="site-switch">
                        @csrf
                        <label for="top-site">当前站点</label>
                        <select id="top-site" name="site_id" onchange="this.form.submit()">
                            @foreach($switchSites as $s)
                                <option value="{{ $s->id }}" @selected((int)($currentSite?->id) === (int)$s->id)>
                                    {{ $s->name }}{{ $s->is_default ? '（默认）' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a class="btn btn-sm" href="{{ url('/') }}" target="_blank" rel="noopener">看前台</a>
                @if($headerInbox['total'] > 0)
                    <a class="topbar-bell" href="{{ route('admin.dashboard') }}#dash-todos" title="{{ $headerInbox['hint'] }}">
                        <i class="fas fa-inbox"></i>
                        <span class="topbar-bell-count">{{ $headerInbox['total'] }}</span>
                    </a>
                @endif
                <details class="account-menu">
                    <summary class="user-chip">
                        <span class="avatar">{{ mb_substr($admin?->name ?? 'A', 0, 1) }}</span>
                        <span>{{ $admin?->name }}</span>
                    </summary>
                    <div class="account-menu-panel">
                        <p class="muted">{{ $admin?->adminRole?->name ?? '管理员' }}</p>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="btn btn-muted btn-sm" type="submit">退出登录</button>
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
                        <span><i class="fas fa-{{ $pageIcon }}"></i> @yield('title', '后台')</span>
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
        function toggle() {
            side?.classList.toggle('open');
            backdrop?.classList.toggle('show');
        }
        document.getElementById('toggleSide')?.addEventListener('click', toggle);
        backdrop?.addEventListener('click', toggle);
    })();
</script>
@stack('scripts')
</body>
</html>
