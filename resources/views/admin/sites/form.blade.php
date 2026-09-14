@extends('admin.layout')
@section('title', $site->exists ? '编辑站点' : '新建站点')
@section('icon', 'globe')
@section('content')
    <form method="post" action="{{ $site->exists ? route('admin.sites.update', $site) : route('admin.sites.store') }}">
        @csrf
        @if($site->exists) @method('PUT') @endif
        <label>站点名称</label>
        <input type="text" name="name" value="{{ old('name', $site->name) }}" required>
        <label>绑定域名（不含协议，如 www.example.com 或 localhost）</label>
        <input type="text" name="domain" value="{{ old('domain', $site->domain) }}" required>
        <label>主题覆盖（留空则用该站「站点设置」中的主题）</label>
        <select name="theme">
            <option value="">使用站点设置</option>
            @foreach($themes as $themeKey)
                <option value="{{ $themeKey }}" @selected(old('theme', $site->theme) === $themeKey)>{{ $themeKey }}</option>
            @endforeach
        </select>
        <label>备注</label>
        <input type="text" name="remark" value="{{ old('remark', $site->remark) }}">
        <label><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $site->is_default))> 设为默认站点</label>
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $site->is_active ?? true))> 启用</label>
        @unless($site->exists)
            <p class="muted">新建站点会从默认站复制基础设置、语言与推荐属性，不会复制栏目与内容。</p>
        @endunless
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.sites.index') }}">返回</a>
        </p>
    </form>
@endsection
