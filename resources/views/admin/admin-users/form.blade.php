@extends('admin.layout')
@section('title', $user->exists ? '编辑管理员' : '新建管理员')
@section('icon', 'user-cog')
@php
    $currentId = (int) ($currentId ?? 0);
    $superCount = (int) ($superCount ?? 0);
    $isSelf = $user->exists && $currentId === (int) $user->id;
    $isLastSuper = $user->exists && $user->isSuperAdmin() && $superCount <= 1;
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.admin-users.index') }}">{{ admin_back('admins') }}</a>
@endsection
@section('content')
    <form method="post" class="admin-user-form" action="{{ $user->exists ? route('admin.admin-users.update', $user) : route('admin.admin-users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">
        <input type="hidden" name="_has_site_scope" value="1">
        <input type="hidden" name="_has_category_scope" value="1">

        <label for="admin-user-name">名称</label>
        <input id="admin-user-name" type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="如 小王" required autofocus>
        <p class="muted field-hint">后台列表和顶栏看到的名字。</p>

        <label for="admin-user-email">登录邮箱</label>
        <input id="admin-user-email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
        <p class="muted field-hint">用来登录后台，和前台会员不是同一套账号。</p>

        <label for="admin-user-password">登录密码</label>
        <input id="admin-user-password" type="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="8" autocomplete="new-password">
        <p class="muted field-hint">{{ $user->exists ? '留空则不改密码。' : '' }}至少 8 位。</p>

        <label for="admin-user-role">角色</label>
        @if($isLastSuper)
            <input type="hidden" name="role_id" value="{{ $user->role_id }}">
            <p class="muted field-hint">这是最后一位超级管理员，角色不能改。需要先再添一位超级管理员。</p>
        @else
            <select id="admin-user-role" name="role_id" required>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                        {{ $role->name }}{{ $role->is_super ? '（能做所有事）' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="muted field-hint">决定能进哪些后台菜单。要改菜单权限，去「权限」。</p>
        @endif

        @if($isSelf || $isLastSuper)
            <input type="hidden" name="is_active" value="1">
            <p class="muted field-hint">{{ $isSelf ? '这是你正在用的账号，不能停用。' : '最后一位超级管理员不能停用。' }}</p>
        @else
            <label class="inline">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                启用，可以登录后台
            </label>
        @endif

        <h3>能管哪些网站</h3>
        <p class="muted field-hint">超级管理员不用勾，全部都能改。其他人至少应能进一个站。</p>
        <div class="admin-user-checks">
            @foreach($sites as $site)
                <label class="inline">
                    <input type="checkbox" name="site_ids[]" value="{{ $site->id }}"
                        @checked(in_array($site->id, old('site_ids', $user->sites?->pluck('id')->all() ?? []), false))>
                    {{ $site->name }}
                    <span class="muted">{{ $site->domain }}</span>
                </label>
            @endforeach
        </div>

        <h3>能改哪些栏目</h3>
        <p class="muted field-hint">不勾表示这个站的栏目都能改。勾了就只能改勾上的。</p>
        <div class="admin-user-checks">
            @forelse($categories as $cat)
                <label class="inline">
                    <input type="checkbox" name="allowed_category_ids[]" value="{{ $cat->id }}"
                        @checked(in_array($cat->id, old('allowed_category_ids', $user->allowed_category_ids ?? []), false))>
                    {{ $cat->t('title') }}
                </label>
            @empty
                <p class="muted">还没有栏目。</p>
            @endforelse
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $user->exists ? '保存' : '添加管理员' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.admin-users.index') }}">取消</a>
        </div>
    </form>
@endsection
