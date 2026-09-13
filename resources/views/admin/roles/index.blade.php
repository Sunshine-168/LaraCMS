@extends('admin.layout')
@section('title', '角色权限')
@section('icon', 'user-shield')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.roles.create') }}">新建角色</a>
@endsection
@section('content')
    <table>
        <thead><tr><th>ID</th><th>名称</th><th>标识</th><th>类型</th><th>权限数</th><th>人数</th><th></th></tr></thead>
        <tbody>
        @foreach($roles as $role)
            <tr>
                <td>{{ $role->id }}</td>
                <td>{{ $role->name }}</td>
                <td>{{ $role->slug }}</td>
                <td>{{ $role->is_super ? '超级管理员' : '自定义' }}</td>
                <td>{{ $role->is_super ? '全部' : $role->permissions_count }}</td>
                <td>{{ $role->users_count }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.roles.edit', $role) }}">编辑</a>
                    @unless($role->is_super)
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="post" onsubmit="return confirm('确认删除？')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">删除</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
