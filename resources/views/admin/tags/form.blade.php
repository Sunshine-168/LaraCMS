@extends('admin.layout')
@section('title', $tag->exists ? '编辑标签' : '新建标签')
@section('icon', 'tags')
@php
    $default = \App\Support\Locale::default();
    $localeMap = \App\Support\Locale::all();
    $otherCodes = array_values(array_filter(\App\Support\Locale::codes(), fn ($c) => $c !== $default));
    $titleVal = old("locales.$default.title", $tag->translation('title', $default));
    $slugVal = old("locales.$default.slug", $tag->translation('slug', $default));
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.tags.index') }}">返回列表</a>
@endsection
@section('content')
    @if($tag->exists && ($tag->entries_count ?? 0) > 0)
        <p class="muted recycle-lead">
            有 <a href="{{ route('admin.entries.index', ['tag_id' => $tag->id]) }}">{{ $tag->entries_count }} 篇</a> 内容使用此标签。改名称不会拆掉已打的标。
        </p>
    @endif
    <form method="post" class="tag-form" action="{{ $tag->exists ? route('admin.tags.update', $tag) : route('admin.tags.store') }}">
        @csrf
        @if($tag->exists) @method('PUT') @endif

        <label for="tag-title">名称</label>
        <input id="tag-title" type="text" name="locales[{{ $default }}][title]" value="{{ $titleVal }}" placeholder="如 Laravel" required autofocus>
        <p class="muted field-hint" id="tagSlugHint">保存后生成前台地址 /tag/…</p>

        <details class="entry-seo" @if($tag->exists) open @endif>
            <summary>网址和其他语言</summary>
            <label for="tag-slug">网址标识</label>
            <input id="tag-slug" type="text" name="locales[{{ $default }}][slug]" value="{{ $slugVal }}" placeholder="可空，按名称生成">
            <p class="muted field-hint">改了之后，旧的 /tag/ 地址不会自动跳转。</p>
            @foreach($otherCodes as $code)
                <label>{{ $localeMap[$code]['label'] ?? $code }} 名称</label>
                <input type="text" name="locales[{{ $code }}][title]" value="{{ old("locales.$code.title", $tag->translation('title', $code)) }}" placeholder="可空，默认用上面的名称">
                <label>{{ $localeMap[$code]['label'] ?? $code }} 网址标识</label>
                <input type="text" name="locales[{{ $code }}][slug]" value="{{ old("locales.$code.slug", $tag->translation('slug', $code)) }}" placeholder="可空">
            @endforeach
        </details>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $tag->exists ? '保存' : '添加标签' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.tags.index') }}">取消</a>
        </div>
    </form>
@endsection
@push('scripts')
<script>
(function () {
    var title = document.getElementById('tag-title');
    var slug = document.getElementById('tag-slug');
    var hint = document.getElementById('tagSlugHint');
    if (!title || !hint) return;
    function preview() {
        var custom = (slug && slug.value || '').trim();
        var raw = custom || (title.value || '').trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]+/g, '');
        hint.textContent = raw
            ? ('前台地址 /tag/' + raw)
            : '保存后自动生成前台地址，如 /tag/laravel';
    }
    title.addEventListener('input', preview);
    slug?.addEventListener('input', preview);
    preview();
})();
</script>
@endpush
