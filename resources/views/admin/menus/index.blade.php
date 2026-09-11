@extends('admin.layout')

@section('title', '菜单')

@section('content')
    <p><a class="btn" href="{{ route('admin.menus.create') }}">新建菜单</a></p>
    <table>
        <thead>
        <tr><th>ID</th><th>名称</th><th>位置</th><th>条目</th><th>启用</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($menus as $menu)
            <tr>
                <td>{{ $menu->id }}</td>
                <td>{{ $menu->name }}</td>
                <td>{{ $menu->location }}</td>
                <td>{{ $menu->items->count() }}</td>
                <td>{{ $menu->is_active ? '是' : '否' }}</td>
                <td>
                    <a href="{{ route('admin.menus.edit', $menu) }}">编辑</a>
                    <form action="{{ route('admin.menus.destroy', $menu) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
