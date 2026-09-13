@extends('admin.layout')
@section('title', '内容模型')
@section('icon', 'cube')
@section('fullwidth', true)
@section('content')
    <div class="card card-panel">
        <div class="card-header">
            <span><i class="fas fa-cube"></i> 内容模型</span>
            <div class="actions">
                <a class="btn btn-sm btn-muted" href="{{ route('admin.models.import') }}"><i class="fas fa-file-import"></i> 导入 JSON</a>
                <a class="btn btn-sm" href="{{ route('admin.models.create') }}"><i class="fas fa-plus"></i> 新建模型</a>
            </div>
        </div>
        <div class="card-body">
            <p class="muted" style="margin:0">模型决定内容扩展字段与默认模板；支持复制 / 导出 JSON。删除前请先清空该模型下的内容与栏目。</p>
        </div>
    </div>

    @foreach($models as $model)
        <div class="card card-panel" style="margin-top:14px;box-shadow:none">
            <div class="card-header">
                <span>
                    {{ $model->name }}
                    <span class="muted">({{ $model->slug }} / {{ $model->kind }})</span>
                    @if(! $model->is_active)<span class="badge badge-off">停用</span>@endif
                </span>
                <div class="actions">
                    <a class="btn btn-sm" href="{{ route('admin.models.fields.create', $model) }}">加字段</a>
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.models.edit', $model) }}">编辑</a>
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.models.export', $model) }}">导出</a>
                    <form action="{{ route('admin.models.copy', $model) }}" method="post" style="display:inline">
                        @csrf
                        <button class="btn btn-sm btn-muted" type="submit">复制</button>
                    </form>
                    <form action="{{ route('admin.models.destroy', $model) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除该模型？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删模型</button>
                    </form>
                </div>
            </div>
            <div class="card-body" style="padding:0">
                <table class="data" style="border:0;margin:0">
                    <thead><tr><th>字段</th><th>标签</th><th>类型</th><th>栏目绑定</th><th>必填</th><th>筛选</th><th>列表</th><th>操作</th></tr></thead>
                    <tbody>
                    @forelse($model->fields as $field)
                        <tr>
                            <td>{{ $field->name }}</td>
                            <td>{{ $field->label }}</td>
                            <td><span class="badge">{{ $field->type }}</span></td>
                            <td class="muted">{{ $field->categories->isEmpty() ? '全栏目' : $field->categories->count().' 个栏目' }}</td>
                            <td>{{ $field->is_required ? '是' : '' }}</td>
                            <td>{{ $field->is_filter ? '是' : '' }}</td>
                            <td>{{ $field->is_list ? '是' : '' }}</td>
                            <td class="actions">
                                <a class="btn btn-sm btn-muted" href="{{ route('admin.models.fields.edit', [$model, $field]) }}">编辑</a>
                                <form action="{{ route('admin.models.fields.destroy', $field) }}" method="post" onsubmit="return confirm('确认删除？')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">删除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">暂无扩展字段</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
