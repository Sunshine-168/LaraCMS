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
        : ['total' => 0, 'hint' => ''];
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
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i><span>仪表盘</span>
                    </a>
                @endif
                @if($can('stats'))
                    <a href="{{ route('admin.stats.index') }}" class="{{ request()->routeIs('admin.stats.*') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i><span>流量统计</span>
                    </a>
                @endif
            @endif

            @if($can('entries') || $can('recycle') || $can('categories') || $can('tags') || $can('recommends') || $can('models') || $can('media') || $can('menus') || $can('forms') || $can('form_submissions') || $can('comments'))
                <div class="nav-header">内容</div>
                @if($can('entries'))
                    <a href="{{ route('admin.entries.index') }}" class="{{ request()->routeIs('admin.entries.*') ? 'active' : '' }}">
                        <i class="fas fa-file-alt"></i><span>内容管理</span>
                        @php $pendingEntries = \App\Models\Cms\Entry::query()->where('status', 'pending')->count(); @endphp
                        @if($pendingEntries > 0)
                            <span class="badge badge-warn" style="margin-left:6px">{{ $pendingEntries }}</span>
                        @endif
                    </a>
                @endif
                @if($can('recycle'))
                    <a href="{{ route('admin.recycle.index') }}" class="{{ request()->routeIs('admin.recycle.*') ? 'active' : '' }}">
                        <i class="fas fa-trash-alt"></i><span>回收站</span>
                    </a>
                @endif
                @if($can('categories'))
                    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i class="fas fa-sitemap"></i><span>栏目管理</span>
                    </a>
                @endif
                @if($can('tags'))
                    <a href="{{ route('admin.tags.index') }}" class="{{ request()->routeIs('admin.tags.*') ? 'active' : '' }}">
                        <i class="fas fa-tags"></i><span>标签管理</span>
                    </a>
                @endif
                @if($can('flags'))
                    <a href="{{ route('admin.flags.index') }}" class="{{ request()->routeIs('admin.flags.*') ? 'active' : '' }}">
                        <i class="fas fa-flag"></i><span>推荐属性</span>
                    </a>
                @endif
                @if($can('recommends'))
                    <a href="{{ route('admin.recommends.index') }}" class="{{ request()->routeIs('admin.recommends.*') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i><span>推荐位</span>
                    </a>
                @endif
                @if($can('models'))
                    <a href="{{ route('admin.models.index') }}" class="{{ request()->routeIs('admin.models.*') ? 'active' : '' }}">
                        <i class="fas fa-cube"></i><span>内容模型</span>
                    </a>
                @endif
                @if($can('media'))
                    <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
                        <i class="fas fa-photo-video"></i><span>媒体库</span>
                    </a>
                @endif
                @if($can('menus'))
                    <a href="{{ route('admin.menus.index') }}" class="{{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">
                        <i class="fas fa-bars"></i><span>导航菜单</span>
                    </a>
                @endif
                @if($can('forms'))
                    <a href="{{ route('admin.forms.index') }}" class="{{ request()->routeIs('admin.forms.*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i><span>表单定义</span>
                    </a>
                @endif
                @if($can('form_submissions'))
                    <a href="{{ route('admin.form-submissions.index') }}" class="{{ request()->routeIs('admin.form-submissions.*') ? 'active' : '' }}">
                        <i class="fas fa-inbox"></i><span>留言收件箱</span>
                        @php $unreadForms = \App\Models\Cms\FormSubmission::query()->where('is_read', false)->count(); @endphp
                        @if($unreadForms > 0)
                            <span class="badge badge-warn" style="margin-left:6px">{{ $unreadForms }}</span>
                        @endif
                    </a>
                @endif
                @if($can('comments'))
                    <a href="{{ route('admin.comments.index') }}" class="{{ request()->routeIs('admin.comments.*') ? 'active' : '' }}">
                        <i class="fas fa-comments"></i><span>评论审核</span>
                    </a>
                @endif
            @endif

            @if($can('collectors') || $can('members') || $can('member_groups') || $can('html_cache') || $can('redirects'))
                <div class="nav-header">运营</div>
                @if($can('redirects'))
                    <a href="{{ route('admin.redirects.index') }}" class="{{ request()->routeIs('admin.redirects.*') ? 'active' : '' }}">
                        <i class="fas fa-exchange-alt"></i><span>重定向</span>
                    </a>
                @endif
                @if($can('collectors'))
                    <a href="{{ route('admin.collectors.index') }}" class="{{ request()->routeIs('admin.collectors.*') ? 'active' : '' }}">
                        <i class="fas fa-cloud-download-alt"></i><span>采集任务</span>
                    </a>
                @endif
                @if($can('members'))
                    <a href="{{ route('admin.members.index') }}" class="{{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i><span>会员管理</span>
                    </a>
                @endif
                @if($can('member_groups'))
                    <a href="{{ route('admin.member-groups.index') }}" class="{{ request()->routeIs('admin.member-groups.*') ? 'active' : '' }}">
                        <i class="fas fa-user-tag"></i><span>会员分组</span>
                    </a>
                @endif
                @if($can('html_cache'))
                    <a href="{{ route('admin.html-cache.edit') }}" class="{{ request()->routeIs('admin.html-cache.*') ? 'active' : '' }}">
                        <i class="fas fa-bolt"></i><span>全页缓存</span>
                    </a>
                @endif
            @endif

            @if($can('sites') || $can('settings') || $can('logs') || $can('admin_users') || $can('roles') || $can('locales'))
                <div class="nav-header">系统</div>
                @if($can('sites'))
                    <a href="{{ route('admin.sites.index') }}" class="{{ request()->routeIs('admin.sites.*') ? 'active' : '' }}">
                        <i class="fas fa-globe"></i><span>站点管理</span>
                    </a>
                @endif
                @if($can('settings'))
                    <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i><span>站点设置</span>
                    </a>
                    <a href="{{ route('admin.cache.edit') }}" class="{{ request()->routeIs('admin.cache.*') ? 'active' : '' }}">
                        <i class="fas fa-tools"></i><span>系统工具</span>
                    </a>
                @endif
                @if($can('locales'))
                    <a href="{{ route('admin.locales.index') }}" class="{{ request()->routeIs('admin.locales.*') ? 'active' : '' }}">
                        <i class="fas fa-language"></i><span>语言管理</span>
                    </a>
                @endif
                @if($can('logs'))
                    <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i><span>操作日志</span>
                    </a>
                @endif
                @if($can('admin_users'))
                    <a href="{{ route('admin.admin-users.index') }}" class="{{ request()->routeIs('admin.admin-users.*') ? 'active' : '' }}">
                        <i class="fas fa-user-cog"></i><span>管理员</span>
                    </a>
                @endif
                @if($can('roles'))
                    <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i class="fas fa-user-shield"></i><span>角色权限</span>
                    </a>
                @endif
            @endif
        </nav>
        <div class="side-foot">
            角色：{{ $admin?->adminRole?->name ?? '—' }}
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
