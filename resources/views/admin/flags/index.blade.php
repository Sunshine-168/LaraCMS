@extends('admin.layout')
@section('title', 'Flags 字典')
@section('icon', 'flag')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.flags.create') }}">新建 Flag</a>
@endsection
@section('content')
    <table>
        <thead><tr><th>标识</th><th>标题</th><th>排序</th><th>状态</th><th></th></tr></thead>
        <tbody>
        @foreach($flags as $flag)
            <tr>
                <td>{{ $flag->name }}</td>
                <td>{{ $flag->title }}</td>
                <td>{{ $flag->sort }}</td>
                <td>{{ $flag->is_active ? '启用' : '停用' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.flags.edit', $flag) }}">编辑</a>
                    <form action="{{ route('admin.flags.destroy', $flag) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
