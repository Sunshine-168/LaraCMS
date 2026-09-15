@extends('admin.layout')
@section('title', $model->exists ? '编辑模型' : '新建模型')
@section('icon', 'cube')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.models.index') }}">{{ admin_back('models') }}</a>
@endsection
@section('content')
    <form method="post" class="model-form" action="{{ $model->exists ? route('admin.models.update', $model) : route('admin.models.store') }}">
        @csrf
        @if($model->exists) @method('PUT') @endif

        <label for="model-name">名称</label>
        <input id="model-name" type="text" name="name" value="{{ old('name', $model->name) }}" placeholder="如 产品" required autofocus>
        <p class="muted field-hint">内容 Tab 和「新建」按钮上看到的名字。</p>

        <label for="model-slug">标识码</label>
        <input id="model-slug" type="text" name="slug" value="{{ old('slug', $model->slug) }}" placeholder="如 product" required {{ $model->exists ? 'readonly' : '' }}>
        <p class="muted field-hint">{{ $model->exists ? '创建后不能改，主题和栏目都靠它认模型。' : '小写字母、数字、下划线。创建后不能改。' }}</p>

        <label>形态</label>
        <select name="kind">
            <option value="list" @selected(old('kind', $model->kind ?: 'list') === 'list')>列表（多篇，如文章、产品）</option>
            <option value="single" @selected(old('kind', $model->kind) === 'single')>单页（一篇，如关于我们）</option>
        </select>

        <details class="entry-seo">
            <summary>模板和开关</summary>
            <label>列表模板</label>
            <input type="text" name="list_tpl" value="{{ old('list_tpl', $model->list_tpl) }}" placeholder="list.list">
            <label>详情模板</label>
            <input type="text" name="show_tpl" value="{{ old('show_tpl', $model->show_tpl ?: 'show.show') }}" placeholder="show.show">
            <label>排序</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $model->sort_order ?? 0) }}">
            <label class="inline"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $model->is_active ?? true))> 启用</label>
            <label class="inline"><input type="hidden" name="is_searchable" value="0"><input type="checkbox" name="is_searchable" value="1" @checked(old('is_searchable', $model->is_searchable ?? true))> 参与全站搜索</label>
            <label class="inline"><input type="hidden" name="allow_contribute" value="0"><input type="checkbox" name="allow_contribute" value="1" @checked(old('allow_contribute', $model->allow_contribute ?? false))> 允许会员投稿（仅列表）</label>
        </details>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $model->exists ? '保存' : '添加模型' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.models.index') }}">取消</a>
        </div>
    </form>
@endsection

