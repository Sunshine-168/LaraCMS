@extends('admin.layout')
@section('title', $locale->exists ? '编辑语言' : '新建语言')
@section('content')
    <form method="post" action="{{ $locale->exists ? route('admin.locales.update', $locale) : route('admin.locales.store') }}">
        @csrf
        @if($locale->exists) @method('PUT') @endif
        <label>代码（如 zh_cn / en / ja）</label>
        <input type="text" name="code" value="{{ old('code', $locale->code) }}" required {{ $locale->exists && $locale->is_default ? 'readonly' : '' }}>
        <label>名称</label>
        <input type="text" name="label" value="{{ old('label', $locale->label) }}" required>
        <label>本地显示名</label>
        <input type="text" name="native" value="{{ old('native', $locale->native) }}">
        <label>排序</label>
        <input type="number" name="sort" value="{{ old('sort', $locale->sort ?? 0) }}">
        <label><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $locale->is_default))> 设为默认语言</label>
        <label><input type="checkbox" name="hide_default" value="1" @checked(old('hide_default', $locale->hide_default ?? true))> 隐藏默认语言 URL 前缀</label>
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $locale->is_active ?? true))> 启用</label>
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.locales.index') }}">返回</a>
        </p>
    </form>
@endsection
