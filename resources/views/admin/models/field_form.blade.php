@extends('admin.layout')
@section('title', ($field->exists ? '编辑字段' : '加字段').' · '.$model->name)
@section('icon', 'cube')
@php
    $types = ['text','textarea','editor','number','url','image','images','file','date','datetime','select','radio','checkbox','switch','map','entry'];
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.models.index') }}">{{ admin_back('models') }}</a>
@endsection
@section('content')
    <form method="post" class="model-form" action="{{ $field->exists ? route('admin.models.fields.update', [$model, $field]) : route('admin.models.fields.store', $model) }}">
        @csrf
        @if($field->exists) @method('PUT') @endif

        <label for="field-label">显示名</label>
        <input id="field-label" type="text" name="label" value="{{ old('label', $field->label) }}" placeholder="如 价格" required autofocus>
        <p class="muted field-hint">写内容页上看到的名字。</p>

        <label for="field-name">字段名</label>
        <input id="field-name" type="text" name="name" value="{{ old('name', $field->name) }}" placeholder="如 price" required>
        <p class="muted field-hint">英文字母开头，给模板用。建好后尽量不要改。</p>

        <label>类型</label>
        <select name="type">
            @foreach($types as $t)
                <option value="{{ $t }}" @selected(old('type', $field->type) === $t)>{{ \App\Models\Cms\ModelField::typeLabel($t) }}</option>
            @endforeach
        </select>

        <details class="entry-seo">
            <summary>选项和出现位置</summary>
            <label>关联模型标识码（关联文档类型）</label>
            <input type="text" name="related_model" value="{{ old('related_model', $field->options['related_model'] ?? ($field->name === 'manga_id' ? 'manga' : '')) }}" placeholder="如 manga">
            <label>选项（下拉 / 单选，逗号分隔）</label>
            <input type="text" name="choices" value="{{ old('choices', implode(',', $field->options['choices'] ?? [])) }}" placeholder="如 红,蓝,绿">
            <label>排序</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $field->sort_order ?? 0) }}">
            <label class="inline"><input type="checkbox" name="is_required" value="1" @checked(old('is_required', $field->is_required))> 必填</label>
            <label class="inline"><input type="checkbox" name="is_filter" value="1" @checked(old('is_filter', $field->is_filter))> 可筛选</label>
            <label class="inline"><input type="checkbox" name="is_list" value="1" @checked(old('is_list', $field->is_list))> 列表显示</label>
        </details>

        <h3>绑定栏目</h3>
        <p class="muted field-hint">不选 = 该模型下所有栏目都出现。勾选后只在这些主栏目的编辑页显示。</p>
        @php
            $bound = old('category_ids', $field->relationLoaded('categories')
                ? $field->categories->pluck('id')->all()
                : ($field->exists ? $field->categories()->pluck('cms_categories.id')->all() : []));
            $bound = array_map('intval', (array) $bound);
        @endphp
        <input type="hidden" name="bind_categories" value="1">
        @forelse(($bindCategories ?? []) as $cat)
            <label class="inline" style="min-width:160px;margin:4px 8px 4px 0">
                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" @checked(in_array((int) $cat->id, $bound, true))>
                {{ $cat->t('title') }}
            </label>
        @empty
            <p class="muted">该模型下还没有栏目。</p>
        @endforelse

        <div class="form-actions">
            <button class="btn" type="submit">{{ $field->exists ? '保存' : '添加字段' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.models.index') }}">取消</a>
        </div>
    </form>
@endsection
