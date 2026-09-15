@extends('admin.layout')
@section('title', '全页缓存')
@section('icon', 'bolt')
@php
    $enabled = (bool) ($enabled ?? false);
    $ttl = (int) ($ttl ?? 3600);
    $ttlOptions = $ttlOptions ?? \App\Services\Cms\HtmlCacheService::ttlOptions();
    $lastBust = $lastBust ?? null;
    $lastBustLabel = $lastBustLabel ?? '还没有刷新过';
    $diskEnabled = (bool) ($diskEnabled ?? false);
    $diskCount = (int) ($diskCount ?? 0);
@endphp
@section('content')
    <div class="html-cache-page">
        <p class="muted recycle-lead">把首页、栏目和文章先做好存起来，访客打开更快。后台、会员中心、搜索和下载不会存。改内容后会自动换新的。</p>

        <div class="html-cache-card">
            <div class="html-cache-status">
                <h3>页面缓存</h3>
                @if($enabled)
                    <span class="badge badge-ok">已开启</span>
                @else
                    <span class="badge badge-off">未开启</span>
                @endif
            </div>
            <form method="post" action="{{ route('admin.html-cache.update') }}">
                @csrf
                @method('PUT')
                <label class="inline">
                    <input type="hidden" name="html_cache_enabled" value="0">
                    <input type="checkbox" name="html_cache_enabled" value="1" @checked($enabled)>
                    启用，访客看到的是刚生成好的页面
                </label>
                <p class="muted field-hint">适合访问多、改动不那么频繁的站点。刚发布的改动最多等你选的时长就会出现。</p>

                <label for="html-cache-ttl">保存多久</label>
                <select id="html-cache-ttl" class="html-cache-ttl" name="html_cache_ttl">
                    @foreach($ttlOptions as $seconds => $label)
                        <option value="{{ $seconds }}" @selected($ttl === (int) $seconds)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="muted field-hint">选「马上换新」时，改完内容前台就会用新页面；选一段时间则期间都用存好的。</p>

                <div class="form-actions">
                    <button class="btn" type="submit">保存设置</button>
                </div>
            </form>
        </div>

        <div class="html-cache-card">
            <h3>现在</h3>
            <p class="muted" style="margin:0 0 8px">
                {{ $lastBustLabel }}
                @if(!empty($lastBust['at']))
                    · {{ \Illuminate\Support\Carbon::parse($lastBust['at'])->format('Y-m-d H:i') }}
                @endif
            </p>
            <div class="html-cache-actions">
                <form method="post" action="{{ route('admin.html-cache.warm') }}">
                    @csrf
                    <input type="hidden" name="entries" value="30">
                    <button class="btn" type="submit" @disabled(! $enabled)>预热常用页</button>
                </form>
                <form method="post" action="{{ route('admin.html-cache.clear') }}" onsubmit="return confirm('清空后访客第一次打开会稍慢，随后又会存起来。确定？')">
                    @csrf
                    <button class="btn btn-muted" type="submit">清空已存页面</button>
                </form>
            </div>
            <p class="muted field-hint">
                @if($enabled)
                    预热会现在生成首页、栏目和最近 30 篇文章，避免刚打开时前台还要现算。
                @else
                    打开并保存后，才能预热。
                @endif
            </p>
        </div>

        <div class="html-cache-card">
            <h3>磁盘静态页</h3>
            @if($diskEnabled)
                <p class="muted">已经打开。把页面写成文件，给服务器直接读，适合流量很大的站点。</p>
                @if($diskCount > 0)
                    <p>目前有 {{ $diskCount }} 个文件。</p>
                @else
                    <p class="muted">还没有生成过文件。</p>
                @endif
                <div class="html-cache-actions">
                    <form method="post" action="{{ route('admin.html-cache.disk-build') }}">
                        @csrf
                        <input type="hidden" name="entries" value="200">
                        <button class="btn" type="submit">生成静态文件</button>
                    </form>
                    <form method="post" action="{{ route('admin.html-cache.disk-clear') }}" onsubmit="return confirm('删掉这些文件后，前台改回动态生成。确定？')">
                        @csrf
                        <button class="btn-quiet" type="submit">删掉静态文件</button>
                    </form>
                </div>
            @else
                <p class="muted" style="margin:0">这是给服务器直接读文件的进阶能力，默认不用开。需要时让运维打开后再来生成。</p>
            @endif
        </div>
    </div>
@endsection
