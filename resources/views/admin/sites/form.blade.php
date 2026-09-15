@extends('admin.layout')
@section('title', $site->exists ? '编辑站点' : '新建站点')
@section('icon', 'globe')
@php $isCurrent = (bool) ($isCurrent ?? false); @endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.sites.index') }}">返回列表</a>
@endsection
@section('content')
    @if($isCurrent)
        <p class="muted recycle-lead">你正在改这个站。栏目、内容和设置都写在它下面。</p>
    @endif
    <form method="post" class="site-form" action="{{ $site->exists ? route('admin.sites.update', $site) : route('admin.sites.store') }}">
        @csrf
        @if($site->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">

        <label for="site-name">名称</label>
        <input id="site-name" type="text" name="name" value="{{ old('name', $site->name) }}" placeholder="如 英文站" required autofocus>
        <p class="muted field-hint">后台列表和顶栏切换时看到的名字。</p>

        <label for="site-domain">域名</label>
        <input id="site-domain" type="text" name="domain" value="{{ old('domain', $site->domain) }}" placeholder="www.example.com" required>
        <p class="muted field-hint">不用加 http://。填 www.example.com 或 127.0.0.1。访客用这个地址打开对应网站。</p>

        <label for="site-theme">外观主题</label>
        <select id="site-theme" name="theme">
            <option value="">跟站点设置走</option>
            @foreach($themes as $themeKey)
                <option value="{{ $themeKey }}" @selected(old('theme', $site->theme) === $themeKey)>{{ \App\Models\Cms\Site::themeLabel($themeKey) }}</option>
            @endforeach
        </select>
        <p class="muted field-hint">留空则用该站「站点设置」里选的主题。这里填了会盖过去。</p>

        <label for="site-remark">备注</label>
        <input id="site-remark" type="text" name="remark" value="{{ old('remark', $site->remark) }}" placeholder="给自己看，前台看不到">

        @if($site->exists && $site->is_default)
            <input type="hidden" name="is_default" value="1">
            <p class="muted field-hint">这是默认站。访客域名对不上时会打开它。不能停用或删除。</p>
        @else
            <label class="inline">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $site->is_default))>
                设为默认站点
            </label>
            <p class="muted field-hint">域名对不上时，用这个站来接待访客。</p>
        @endif

        @if($site->exists && $site->is_default)
            <input type="hidden" name="is_active" value="1">
        @else
            <label class="inline">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $site->is_active ?? true))>
                启用，这个域名可以打开
            </label>
        @endif

        @unless($site->exists)
            <p class="muted field-hint">会带上默认站的语言、推荐属性和会员分组，不会复制栏目和文章。</p>
        @endunless

        <div class="form-actions">
            <button class="btn" type="submit">{{ $site->exists ? '保存' : '添加站点' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.sites.index') }}">取消</a>
        </div>
    </form>
@endsection
