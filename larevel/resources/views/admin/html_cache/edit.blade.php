@extends('admin.layout')
@section('title', '全页缓存')
@section('content')
    <p class="muted">全页 HTML 缓存：URL 仍用伪静态路由，命中后直接返回 HTML。不缓存后台、会员、表单、下载、搜索等动态页。</p>

    <form method="post" action="{{ route('admin.html-cache.update') }}">
        @csrf
        @method('PUT')
        <label><input type="checkbox" name="html_cache_enabled" value="1" @checked($enabled)> 启用全页缓存</label>
        <label>TTL（秒，0 = 仅靠内容变更失效）</label>
        <input type="number" name="html_cache_ttl" value="{{ old('html_cache_ttl', $ttl) }}" min="0" max="2592000">
        <p style="margin-top:16px"><button class="btn" type="submit">保存</button></p>
    </form>

    <hr style="margin:24px 0;border:none;border-top:1px solid #e5e7eb">

    <p class="muted">
        失效策略：{{ $tagged ? 'Redis/Memcached 标签 + epoch' : 'epoch 全站版本号（当前 CACHE 驱动）' }}
        @if($lastBust)
            · 最近失效：{{ $lastBust['reason'] ?? '-' }} @ {{ $lastBust['at'] ?? '-' }}
        @endif
    </p>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <form method="post" action="{{ route('admin.html-cache.clear') }}" onsubmit="return confirm('确认清空全部页面缓存？')">
            @csrf
            <button class="btn btn-danger" type="submit">清空缓存</button>
        </form>
        <form method="post" action="{{ route('admin.html-cache.warm') }}">
            @csrf
            <input type="hidden" name="entries" value="30">
            <button class="btn" type="submit">预热（首页/栏目/最近30篇）</button>
        </form>
    </div>

    <p class="muted" style="margin-top:16px">
        命令行：<code>php artisan cms:html-clear</code> /
        <code>php artisan cms:html-warm --entries=30</code>
    </p>
@endsection
