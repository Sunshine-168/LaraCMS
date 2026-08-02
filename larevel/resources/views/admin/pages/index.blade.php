@extends('admin.layout')

@section('title', '页面')

@section('content')
    <p><a class="btn" href="{{ route('admin.pages.create') }}">新建页面</a></p>
    <table>
        <thead>
        <tr><th>ID</th><th>标题</th><th>Slug</th><th>状态</th><th>首页</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($pages as $page)
            <tr>
                <td>{{ $page->id }}</td>
                <td>{{ $page->t('title') }}</td>
                <td>{{ $page->t('slug') }}</td>
                <td>{{ $page->status }}</td>
                <td>{{ $page->is_homepage ? '是' : '' }}</td>
                <td>
                    <a href="{{ route('admin.pages.edit', $page) }}">编辑</a>
                    <form action="{{ route('admin.pages.destroy', $page) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $pages->links() }}
@endsection
