@extends('admin.layout')
@section('title', '前台会员')
@section('content')
    <p><a class="btn" href="{{ route('admin.members.create') }}">新建会员</a></p>
    <table>
        <thead><tr><th>ID</th><th>名称</th><th>邮箱</th><th>分组</th><th>状态</th><th></th></tr></thead>
        <tbody>
        @foreach($members as $member)
            <tr>
                <td>{{ $member->id }}</td>
                <td>{{ $member->name }}</td>
                <td>{{ $member->email }}</td>
                <td>{{ $member->group?->name ?? '—' }}</td>
                <td>{{ $member->is_active ? '启用' : '禁用' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.members.edit', $member) }}">编辑</a>
                    <form action="{{ route('admin.members.destroy', $member) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $members->links() }}
@endsection
