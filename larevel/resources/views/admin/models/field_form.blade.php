@extends('admin.layout')
@section('title', ($field->exists ? '编辑' : '新建').'字段 · '.$model->name)
@section('content')
    <form method="post" action="{{ $field->exists ? route('admin.models.fields.update', [$model, $field]) : route('admin.models.fields.store', $model) }}">
        @csrf
        @if($field->exists) @method('PUT') @endif
        <label>字段名（英文）</label>
        <input type="text" name="name" value="{{ old('name', $field->name) }}" required>
        <label>显示名</label>
        <input type="text" name="label" value="{{ old('label', $field->label) }}" required>
        <label>类型</label>
        <select name="type">
            @foreach(['text','textarea','editor','number','url','image','images','file','date','datetime','select','radio','checkbox','switch','map'] as $t)
                <option value="{{ $t }}" @selected(old('type', $field->type) === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <label>选项（select/radio 用，逗号分隔）</label>
        <input type="text" name="choices" value="{{ old('choices', implode(',', $field->options['choices'] ?? [])) }}">
        <label>排序</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $field->sort_order ?? 0) }}">
        <label><input type="checkbox" name="is_required" value="1" @checked(old('is_required', $field->is_required))> 必填</label>
        <label><input type="checkbox" name="is_filter" value="1" @checked(old('is_filter', $field->is_filter))> 可筛选</label>
        <label><input type="checkbox" name="is_list" value="1" @checked(old('is_list', $field->is_list))> 列表/排序显示</label>

        <h3 style="margin-top:18px">绑定栏目（栏目级字段）</h3>
        <p class="muted">不选 = 该模型下所有栏目都显示；勾选后仅在所选主栏目编辑时出现。</p>
        @php
            $bound = old('category_ids', $field->relationLoaded('categories')
                ? $field->categories->pluck('id')->all()
                : ($field->exists ? $field->categories()->pluck('cms_categories.id')->all() : []));
            $bound = array_map('intval', (array) $bound);
        @endphp
        <input type="hidden" name="bind_categories" value="1">
        @forelse(($bindCategories ?? []) as $cat)
            <label style="display:inline-block;min-width:160px;margin:4px 8px 4px 0">
                <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" @checked(in_array((int)$cat->id, $bound, true))>
                {{ $cat->t('title') }}
            </label>
        @empty
            <p class="muted">该模型下尚无栏目</p>
        @endforelse

        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.models.index') }}">返回</a>
        </p>
    </form>
@endsection
