@extends('admin.layout')
@section('title', '表单定义')
@section('icon', 'wpforms')
@section('content')
    <div class="toolbar">
        <a class="btn" href="{{ route('admin.forms.create') }}"><i class="fas fa-plus"></i> 新建表单</a>
        <a class="btn btn-muted" href="{{ route('admin.form-submissions.index') }}"><i class="fas fa-inbox"></i> 留言收件箱</a>
    </div>
    <p class="muted">前台用 <code>@cmsForm(['slug' => 'contact'])</code> 或 POST <code>/form/{slug}</code>。标识 slug 用于模板与路由。</p>
    <table class="data">
        <thead>
        <tr><th>ID</th><th>名称</th><th>Slug</th><th>字段数</th><th>留言</th><th>状态</th><th>操作</th></tr>
        </thead>
        <tbody>
        @forelse($forms as $form)
            <tr>
                <td>{{ $form->id }}</td>
                <td>{{ $form->name }}</td>
                <td><code>{{ $form->slug }}</code></td>
                <td>{{ count($form->fields ?? []) }}</td>
                <td>
                    <a href="{{ route('admin.form-submissions.index', ['form_id' => $form->id]) }}">{{ $form->submissions_count }}</a>
                </td>
                <td>
                    @if($form->is_active)
                        <span class="badge badge-ok">启用</span>
                    @else
                        <span class="badge badge-off">停用</span>
                    @endif
                </td>
                <td class="actions">
                    <a href="{{ route('admin.forms.edit', $form) }}">编辑</a>
                    <form action="{{ route('admin.forms.destroy', $form) }}" method="post" style="display:inline" onsubmit="return confirm('删除表单将同时删除留言，确认？')">
                        @csrf @method('DELETE')
                        <button class="btn-link" type="submit" style="color:#dc3545!important">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">暂无表单，请新建</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $forms->links() }}
@endsection
