@extends('admin.layout')
@section('title', $group->exists ? '编辑分组' : '新建分组')
@section('content')
    <form method="post" action="{{ $group->exists ? route('admin.member-groups.update', $group) : route('admin.member-groups.store') }}">
        @csrf
        @if($group->exists) @method('PUT') @endif
        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $group->name) }}" required>
        <label>标识（slug，可空自动生成）</label>
        <input type="text" name="slug" value="{{ old('slug', $group->slug) }}">
        <label>排序</label>
        <input type="number" name="sort" value="{{ old('sort', $group->sort ?? 0) }}">
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))> 启用</label>
        @php
            $permLabels = ['download' => '下载受保护资源', 'form_submit' => '提交表单', 'view_member_content' => '查看会员内容'];
            $selected = old('permissions', $group->permissions ?? array_keys($permLabels));
        @endphp
        <h3 style="margin:16px 0 8px">前台权限</h3>
        @foreach($permLabels as $key => $label)
            <label style="display:inline-block;margin-right:14px">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selected, true))>
                {{ $label }}
            </label>
        @endforeach
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.member-groups.index') }}">返回</a>
        </p>
    </form>
@endsection
