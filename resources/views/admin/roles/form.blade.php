@extends('admin.layout')
@section('title', $role->exists ? '编辑角色' : '新建角色')
@section('icon', 'user-shield')
@php
    $selected = old('permissions', $selected ?? []);
    if (! is_array($selected)) {
        $selected = [];
    }
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.roles.index') }}">返回列表</a>
@endsection
@section('content')
    @if($role->exists && ($role->users_count ?? 0) > 0)
        <p class="muted recycle-lead">
            有
            @if(auth('admin')->user()?->canPerm('admin_users'))
                <a href="{{ route('admin.admin-users.index', ['role' => $role->id]) }}">{{ $role->users_count }} 人</a>
            @else
                {{ $role->users_count }} 人
            @endif
            在用这个角色。改权限后，他们下次打开后台就会按新的来。
        </p>
    @endif
    <form method="post" class="role-form" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if($role->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">

        <label for="role-name">名称</label>
        <input id="role-name" type="text" name="name" value="{{ old('name', $role->name) }}" placeholder="如 审核员" required autofocus>
        <p class="muted field-hint">给管理员分角色时看到的名字。</p>

        @if($role->exists)
            <p class="muted field-hint">标识码 {{ $role->slug }}，建好后不用改。</p>
        @else
            <label for="role-slug">标识码</label>
            <input id="role-slug" type="text" name="slug" value="{{ old('slug', $role->slug) }}" placeholder="如 reviewer">
            <p class="muted field-hint">给程序用。可空时按名称生成。</p>
        @endif

        <label for="role-remark">备注</label>
        <input id="role-remark" type="text" name="remark" value="{{ old('remark', $role->remark) }}" placeholder="给自己看，可空">

        @if($role->exists && $role->is_super)
            <p class="muted field-hint">这是超级管理员角色，能进所有菜单，不能改成普通角色。</p>
        @else
            <p class="role-perms-label">能进哪些菜单</p>
            <p class="muted field-hint">不勾的菜单，这个角色的人进后台看不到。</p>
            @foreach($permissionGroups as $group => $perms)
                <div class="role-perm-group">
                    <h3>{{ $group }}</h3>
                    <div class="role-perms">
                        @foreach($perms as $perm)
                            <label class="inline">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->key }}"
                                    @checked(in_array($perm->key, $selected, true))>
                                {{ $perm->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        <div class="form-actions">
            <button class="btn" type="submit">{{ $role->exists ? '保存' : '添加角色' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.roles.index') }}">取消</a>
        </div>
    </form>
@endsection
