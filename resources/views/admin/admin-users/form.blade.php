@extends('admin.layout')
@section('title', $user->exists ? '编辑管理员' : '新建管理员')
@section('icon', 'user-cog')
@section('content')
    <form method="post" action="{{ $user->exists ? route('admin.admin-users.update', $user) : route('admin.admin-users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif
        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        <label>邮箱</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        <label>角色</label>
        <select name="role_id" required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                    {{ $role->name }}{{ $role->is_super ? '（全部权限）' : '' }}
                </option>
            @endforeach
        </select>
        <label>密码{{ $user->exists ? '（留空不改）' : '' }}</label>
        <input type="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="8">
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))> 启用</label>

        <h3 style="margin:18px 0 8px">可管理站点</h3>
        <p class="muted">超级管理员忽略此项（可管理全部站点）。普通管理员至少勾选一个站点。</p>
        <input type="hidden" name="_has_site_scope" value="1">
        <div>
            @foreach($sites as $site)
                <label style="display:inline-block;min-width:180px;margin:4px 8px 4px 0">
                    <input type="checkbox" name="site_ids[]" value="{{ $site->id }}"
                        @checked(in_array($site->id, old('site_ids', $user->sites?->pluck('id')->all() ?? []), false))>
                    {{ $site->name }} <span class="muted">({{ $site->domain }})</span>
                </label>
            @endforeach
        </div>

        <h3 style="margin:18px 0 8px">栏目授权</h3>
        <p class="muted">不勾选表示可管理全部栏目；勾选后仅可管理所选栏目内容。</p>
        <input type="hidden" name="_has_category_scope" value="1">
        <div>
            @foreach($categories as $cat)
                <label style="display:inline-block;min-width:160px;margin:4px 8px 4px 0">
                    <input type="checkbox" name="allowed_category_ids[]" value="{{ $cat->id }}"
                        @checked(in_array($cat->id, old('allowed_category_ids', $user->allowed_category_ids ?? []), false))>
                    {{ $cat->t('title') }}
                </label>
            @endforeach
            @if($categories->isEmpty())
                <p class="muted">暂无栏目</p>
            @endif
        </div>

        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.admin-users.index') }}">返回</a>
        </p>
    </form>
@endsection
