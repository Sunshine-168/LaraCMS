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
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.models.index') }}">返回</a>
        </p>
    </form>
@endsection
