@extends('admin.layout')
@section('title', '管理员')
@section('icon', 'user-cog')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.admin-users.create') }}">新建管理员</a>
@endsection
@section('content')
    <table>
        <thead><tr><th>ID</th><th>名称</th><th>邮箱</th><th>角色</th><th>状态</th><th></th></tr></thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->adminRole?->name ?? '—' }}</td>
                <td>{{ $user->is_active ? '启用' : '禁用' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.admin-users.edit', $user) }}">编辑</a>
                    <form action="{{ route('admin.admin-users.destroy', $user) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $users->links() }}
@endsection
