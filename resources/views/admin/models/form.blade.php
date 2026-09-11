@extends('admin.layout')
@section('title', $model->exists ? '编辑模型' : '新建模型')
@section('icon', 'cube')
@section('content')
    <form method="post" action="{{ $model->exists ? route('admin.models.update', $model) : route('admin.models.store') }}">
        @csrf
        @if($model->exists) @method('PUT') @endif

        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $model->name) }}" required>

        <label>标识 slug（小写字母/数字/下划线，创建后慎改）</label>
        <input type="text" name="slug" value="{{ old('slug', $model->slug) }}" required {{ $model->exists ? 'readonly' : '' }}>

        <label>类型</label>
        <select name="kind">
            <option value="list" @selected(old('kind', $model->kind ?: 'list') === 'list')>列表 list</option>
            <option value="single" @selected(old('kind', $model->kind) === 'single')>单页 single</option>
        </select>

        <label>列表模板（如 list.list / list.list_product）</label>
        <input type="text" name="list_tpl" value="{{ old('list_tpl', $model->list_tpl) }}">

        <label>详情模板（如 show.show / show.show_product）</label>
        <input type="text" name="show_tpl" value="{{ old('show_tpl', $model->show_tpl ?: 'show.show') }}">

        <label>排序</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $model->sort_order ?? 0) }}">

        <label class="inline"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $model->is_active ?? true))> 启用</label>
        <label class="inline"><input type="checkbox" name="is_searchable" value="1" @checked(old('is_searchable', $model->is_searchable ?? true))> 参与全站搜索</label>
        <label class="inline"><input type="checkbox" name="allow_contribute" value="1" @checked(old('allow_contribute', $model->allow_contribute ?? false))> 允许会员投稿（仅列表模型）</label>

        <div class="form-actions">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.models.index') }}">返回</a>
        </div>
    </form>
@endsection
