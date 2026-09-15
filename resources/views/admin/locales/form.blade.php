@extends('admin.layout')
@section('title', $locale->exists ? '编辑语言' : '新建语言')
@section('icon', 'language')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.locales.index') }}">返回列表</a>
@endsection
@section('content')
    <form method="post" class="locale-form" action="{{ $locale->exists ? route('admin.locales.update', $locale) : route('admin.locales.store') }}">
        @csrf
        @if($locale->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">

        <label for="locale-label">名称</label>
        <input id="locale-label" type="text" name="label" value="{{ old('label', $locale->label) }}" placeholder="如 日本語" required autofocus>
        <p class="muted field-hint">后台列表里看到的名字。</p>

        <label for="locale-native">前台怎么写</label>
        <input id="locale-native" type="text" name="native" value="{{ old('native', $locale->native) }}" placeholder="如 日本語">
        <p class="muted field-hint">访客切换语言时看到的。可以和名称一样。</p>

        <label for="locale-code">代码</label>
        <input id="locale-code" type="text" name="code" value="{{ old('code', $locale->code) }}" placeholder="如 ja" required {{ $locale->exists && $locale->is_default ? 'readonly' : '' }}>
        <p class="muted field-hint">地址里用，如 en、ja、zh_cn。默认语言的代码不能改。</p>

        <label for="locale-sort">排列顺序</label>
        <input id="locale-sort" type="number" name="sort" value="{{ old('sort', $locale->sort ?? 0) }}" min="0" max="9999">
        <p class="muted field-hint">数字越小越靠前。前台切换语言时也按这个顺序。</p>

        @if($locale->exists && $locale->is_default)
            <input type="hidden" name="is_default" value="1">
            <p class="muted field-hint">这是默认语言。访客没选语言时用它。不能停用或删除。</p>
        @else
            <label class="inline">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $locale->is_default))>
                设为默认语言
            </label>
            <p class="muted field-hint">没选语言时，用这种语言接待访客。</p>
        @endif

        @if($locale->exists && $locale->is_default)
            <label class="inline">
                <input type="hidden" name="hide_default" value="0">
                <input type="checkbox" name="hide_default" value="1" @checked(old('hide_default', $locale->hide_default ?? true))>
                默认语言的网址不加前缀
            </label>
            <p class="muted field-hint">打开后中文首页是 / 而不是 /zh。其它语言仍然带前缀，如 /en。</p>
        @endif

        @if($locale->exists && $locale->is_default)
            <input type="hidden" name="is_active" value="1">
        @else
            <label class="inline">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $locale->is_active ?? true))>
                启用，前台可以切换
            </label>
        @endif

        <div class="form-actions">
            <button class="btn" type="submit">{{ $locale->exists ? '保存' : '添加语言' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.locales.index') }}">取消</a>
        </div>
    </form>
@endsection
