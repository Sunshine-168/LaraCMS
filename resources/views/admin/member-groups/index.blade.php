@extends('admin.layout')
@section('title', '会员分组')
@section('content')
    <p><a class="btn" href="{{ route('admin.member-groups.create') }}">新建分组</a></p>
    <table class="table">
        <thead><tr><th>ID</th><th>名称</th><th>标识</th><th>排序</th><th>状态</th><th></th></tr></thead>
        <tbody>
        @forelse($groups as $g)
            <tr>
                <td>{{ $g->id }}</td>
                <td>{{ $g->name }}</td>
                <td>{{ $g->slug }}</td>
                <td>{{ $g->sort }}</td>
                <td>{{ $g->is_active ? '启用' : '停用' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.member-groups.edit', $g) }}">编辑</a>
                    <form method="post" action="{{ route('admin.member-groups.destroy', $g) }}" onsubmit="return confirm('确定删除？')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">暂无分组</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
