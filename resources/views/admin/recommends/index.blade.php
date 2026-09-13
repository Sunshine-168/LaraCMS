@extends('admin.layout')
@section('title', '推荐位')
@section('content')
    <p><a class="btn" href="{{ route('admin.recommends.create') }}">新建推荐位</a></p>
    <table>
        <thead><tr><th>标识</th><th>标题</th><th>类型</th><th>条目</th><th></th></tr></thead>
        <tbody>
        @foreach($recommends as $recommend)
            <tr>
                <td>{{ $recommend->name }}</td>
                <td>{{ $recommend->title }}</td>
                <td>{{ $recommend->type }}</td>
                <td>{{ $recommend->items->count() }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.recommends.edit', $recommend) }}">编辑</a>
                    <form action="{{ route('admin.recommends.destroy', $recommend) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
