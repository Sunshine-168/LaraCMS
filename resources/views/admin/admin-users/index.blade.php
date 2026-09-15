@extends('admin.layout')
@section('title', admin_t('nav.admins'))
@section('icon', 'user-cog')
@php
    $q = $q ?? '';
    $roleKey = $roleKey ?? '';
    $inactiveOnly = (bool) ($inactiveOnly ?? false);
    $currentId = (int) ($currentId ?? 0);
    $superCount = (int) ($superCount ?? 0);
    $hasFilters = $q !== '' || $roleKey !== '' || $inactiveOnly;
    $composeFocus = session('admin_user_quick_added') || $users->isEmpty();
    $defaultRoleId = $roles->first(fn ($r) => ! $r->is_super)?->id ?? $roles->first()?->id;
    $qs = function (array $extra = []) use ($q, $roleKey, $inactiveOnly) {
        return route('admin.admin-users.index', array_filter(
            array_merge([
                'q' => $q,
                'role' => $roleKey !== '' ? $roleKey : null,
                'off' => $inactiveOnly ? '1' : null,
            ], $extra),
            fn ($v) => $v !== '' && $v !== null
        ));
    };
@endphp
@section('header_actions')
    @if(auth('admin')->user()?->canPerm('roles'))
        <a class="btn btn-muted btn-sm" href="{{ route('admin.roles.index') }}">权限</a>
    @endif
@endsection
@section('content')
    <div class="entry-index admin-user-index">
        <div class="admin-user-compose">
            <form method="post" action="{{ route('admin.admin-users.store') }}">
                @csrf
                <input type="hidden" name="is_active" value="1">
                <label class="admin-user-compose-label" for="admin-user-quick-name">新增管理员</label>
                <div class="admin-user-compose-row">
                    <input id="admin-user-quick-name" type="text" name="name" value="" placeholder="名称，如 小王" aria-label="名称" required @if($composeFocus) autofocus @endif>
                    <input type="email" name="email" value="" placeholder="登录邮箱" aria-label="登录邮箱" required>
                    <input type="password" name="password" value="" placeholder="密码，至少 8 位" aria-label="密码" required minlength="8" autocomplete="new-password">
                    <select name="role_id" aria-label="角色" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) $defaultRoleId === (int) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn" type="submit">添加</button>
                </div>
                <p class="muted field-hint">添加后可以登录后台。需要限制能管哪些网站或栏目时，<a href="{{ route('admin.admin-users.create') }}">打开完整表单</a>。</p>
            </form>
        </div>

        <div class="entry-find">
            <form method="get" class="filter-bar admin-user-find" action="{{ route('admin.admin-users.index') }}">
                @if($roleKey !== '')
                    <input type="hidden" name="role" value="{{ $roleKey }}">
                @endif
                @if($inactiveOnly)
                    <input type="hidden" name="off" value="1">
                @endif
                <input type="search" name="q" value="{{ $q }}" placeholder="搜索姓名或邮箱" aria-label="搜索管理员">
                <button class="btn btn-muted" type="submit">搜索</button>
            </form>
            <div class="entry-status-row">
                <a class="chip {{ $roleKey === '' && ! $inactiveOnly ? 'active' : '' }}" href="{{ route('admin.admin-users.index', array_filter(['q' => $q ?: null])) }}">全部</a>
                @foreach($roles as $role)
                    <a class="chip {{ $roleKey === (string) $role->id ? 'active' : '' }}" href="{{ $qs(['role' => (string) $role->id, 'off' => null]) }}">{{ $role->name }}</a>
                @endforeach
                <a class="chip {{ $inactiveOnly ? 'active' : '' }}" href="{{ $qs(['off' => $inactiveOnly ? null : '1']) }}">已停用</a>
                @if($hasFilters)
                    <a class="chip" href="{{ route('admin.admin-users.index') }}">清除筛选</a>
                @endif
                @if($users->isNotEmpty())
                    <span class="muted entry-find-hint">点名称去改角色和能管哪些站。停用后不能登录后台。不能删自己，至少留一位超级管理员。</span>
                @endif
            </div>
        </div>

        @if($users->isEmpty())
            <div class="list-empty">
                @if($hasFilters)
                    <p>没有符合条件的管理员。</p>
                    <a class="btn btn-muted" href="{{ route('admin.admin-users.index') }}">清除筛选</a>
                @else
                    <p>还没有管理员。</p>
                    <p class="muted">在上方填写名称、邮箱和密码即可添加。</p>
                @endif
            </div>
        @else
            <table class="data">
                <thead>
                <tr>
                    <th>管理员</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    @php
                        $isSelf = $currentId === (int) $user->id;
                        $isLastSuper = $user->isSuperAdmin() && $superCount <= 1;
                    @endphp
                    <tr @class(['is-off' => ! $user->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.admin-users.edit', $user) }}">{{ $user->name ?: '未命名' }}</a>
                                @if($isSelf)
                                    <span class="badge badge-ok">当前账号</span>
                                @endif
                                @unless($user->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">
                                {{ $user->email }}
                                · {{ $user->adminRole?->name ?: '未分角色' }}
                                @if($user->adminRole?->is_super)
                                    · 能做所有事
                                @elseif($user->sites->isNotEmpty())
                                    · {{ $user->sites->pluck('name')->join('、') }}
                                @endif
                                @if($user->last_login_at)
                                    · 上次登录 {{ $user->last_login_at->isToday() ? '今天 '.$user->last_login_at->format('H:i') : ($user->last_login_at->isYesterday() ? '昨天 '.$user->last_login_at->format('H:i') : $user->last_login_at->format('m-d H:i')) }}
                                    @if($user->last_login_ip)
                                        @php $loginPlace = \App\Support\IpLocation::label($user->last_login_ip); @endphp
                                        @if(auth('admin')->user()?->canPerm('logs'))
                                            · <a class="log-ip" href="{{ route('admin.logs.index', ['ip' => $user->last_login_ip]) }}">{{ $user->last_login_ip }}</a>
                                        @else
                                            · {{ $user->last_login_ip }}
                                        @endif
                                        @if($loginPlace)
                                            <span class="log-ip-place">{{ $loginPlace }}</span>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="actions">
                            @unless($isSelf || $isLastSuper)
                                <form action="{{ route('admin.admin-users.toggle', $user) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm btn-muted" type="submit">{{ $user->is_active ? '停用' : '启用' }}</button>
                                </form>
                                <form action="{{ route('admin.admin-users.destroy', $user) }}" method="post" onsubmit="return confirm(@json('确定删除「'.($user->name ?: $user->email).'」？不能再登录后台。'))">
                                    @csrf @method('DELETE')
                                    <button class="btn-quiet" type="submit">删除</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $users->total() }} 人</p>
            {{ $users->links() }}
        @endif
    </div>
@endsection
