@extends('admin.layout')
@section('title', $role->exists ? '编辑角色' : '新建角色')
@section('icon', 'user-shield')
@section('content')
    <form method="post" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if($role->exists) @method('PUT') @endif
        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $role->name) }}" required>
        @unless($role->exists)
            <label>标识（slug，可空）</label>
            <input type="text" name="slug" value="{{ old('slug', $role->slug) }}">
        @endunless
        <label>备注</label>
        <input type="text" name="remark" value="{{ old('remark', $role->remark) }}">
        @unless($role->exists && $role->is_super)
            <label><input type="checkbox" name="is_super" value="1" @checked(old('is_super', $role->is_super))> 超级管理员（拥有全部权限）</label>
        @else
            <p class="muted">超级管理员角色，自动拥有全部权限。</p>
            <input type="hidden" name="is_super" value="1">
        @endunless

        @unless(old('is_super', $role->is_super))
            <h3 style="margin:20px 0 10px">模块权限</h3>
            @foreach($permissionGroups as $group => $perms)
                <fieldset style="margin-bottom:14px;border:1px solid #dee2e6;padding:12px">
                    <legend>{{ $group }}</legend>
                    @foreach($perms as $perm)
                        <label style="display:inline-block;min-width:160px;margin:4px 8px 4px 0">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->key }}"
                                @checked(in_array($perm->key, old('permissions', $selected), true))>
                            {{ $perm->name }}
                        </label>
                    @endforeach
                </fieldset>
            @endforeach
        @endunless

        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.roles.index') }}">返回</a>
        </p>
    </form>
@endsection
