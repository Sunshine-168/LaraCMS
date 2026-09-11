@extends('admin.layout')

@section('title', '文章')

@section('content')
    <p><a class="btn" href="{{ route('admin.posts.create') }}">新建文章</a></p>
    <table>
        <thead>
        <tr><th>ID</th><th>标题</th><th>分类</th><th>状态</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($posts as $post)
            <tr>
                <td>{{ $post->id }}</td>
                <td>{{ $post->t('title') }}</td>
                <td>{{ $post->category?->t('title') }}</td>
                <td>{{ $post->status }}</td>
                <td>
                    <a href="{{ route('admin.posts.edit', $post) }}">编辑</a>
                    <form action="{{ route('admin.posts.destroy', $post) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $posts->links() }}
@endsection
