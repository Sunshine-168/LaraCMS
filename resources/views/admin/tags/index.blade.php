@extends('admin.layout')
@section('title', '标签管理')
@section('icon', 'tags')
@section('content')
    <div class="toolbar">
        <a class="btn" href="{{ route('admin.tags.create') }}"><i class="fas fa-plus"></i> 新建标签</a>
    </div>
    <table class="data">
        <thead><tr><th>ID</th><th>标题</th><th>Slug</th><th>内容数</th><th>操作</th></tr></thead>
        <tbody>
        @forelse($tags as $tag)
            <tr>
                <td>{{ $tag->id }}</td>
                <td>{{ $tag->t('title') }}</td>
                <td><code>{{ $tag->t('slug') }}</code></td>
                <td>{{ $tag->entries_count }}</td>
                <td class="actions">
                    <a href="{{ cms_front_url('tag/'.$tag->t('slug')) }}" target="_blank">前台</a>
                    <a href="{{ route('admin.tags.edit', $tag) }}">编辑</a>
                    <form action="{{ route('admin.tags.destroy', $tag) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn-link" type="submit" style="color:#dc3545!important">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">暂无标签</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $tags->links() }}
@endsection
