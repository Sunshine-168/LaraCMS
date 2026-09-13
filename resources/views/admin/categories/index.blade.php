@extends('admin.layout')
@section('title', '栏目管理')
@section('icon', 'sitemap')
@section('content')
    <div class="toolbar">
        <a class="btn" href="{{ route('admin.categories.create') }}"><i class="fas fa-plus"></i> 新建栏目</a>
    </div>
    <table class="data">
        <thead><tr><th>ID</th><th>标题</th><th>Slug</th><th>类型</th><th>模型</th><th>菜单</th><th class="col-actions">操作</th></tr></thead>
        <tbody>
        @forelse($categories as $category)
            <tr>
                <td>{{ $category->id }}</td>
                <td>{{ $category->t('title') }}</td>
                <td>{{ $category->t('slug') }}</td>
                <td><span class="badge">{{ $category->type }}</span></td>
                <td>{{ $category->modelDef?->name }}</td>
                <td>{{ $category->is_menu ? '是' : '—' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.categories.edit', $category) }}">编辑</a>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">暂无栏目</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
