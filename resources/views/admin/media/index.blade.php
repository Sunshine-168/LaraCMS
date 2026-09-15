@extends('admin.layout')
@section('title', admin_t('nav.media'))
@section('icon', 'photo-video')
@php
    $q = $q ?? '';
    $type = $type ?? '';
    $hasFilters = $q !== '' || $type !== '';
    $qs = function (array $extra = []) use ($q, $type) {
        return route('admin.media.index', array_filter(
            array_merge(['q' => $q, 'type' => $type], $extra),
            fn ($v) => $v !== '' && $v !== null
        ));
    };
    $fmtSize = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    };
@endphp
@section('content')
    <div class="entry-index media-index">
        <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="media-drop" id="mediaUploadForm">
            @csrf
            <input type="hidden" name="keep_q" value="{{ $q }}">
            <input type="hidden" name="keep_type" value="{{ $type }}">
            <label class="media-drop-label">上传文件</label>
            <div class="media-drop-zone" id="mediaDropZone" tabindex="0" role="button" aria-label="点击或拖入文件">
                <strong>点击或拖入文件</strong>
                <span class="muted">图片、文档都可以，单个不超过 10 MB，可一次多个</span>
                <input type="file" name="files[]" id="mediaFiles" multiple hidden>
            </div>
        </form>

        <form method="get" class="filter-bar tag-find" action="{{ route('admin.media.index') }}">
            @if($type !== '')
                <input type="hidden" name="type" value="{{ $type }}">
            @endif
            <input type="search" name="q" value="{{ $q }}" placeholder="搜索文件名或说明" aria-label="搜索媒体">
            <button class="btn btn-muted" type="submit">搜索</button>
            <a class="chip {{ $type === '' ? 'active' : '' }}" href="{{ $qs(['type' => null]) }}">全部</a>
            <a class="chip {{ $type === 'image' ? 'active' : '' }}" href="{{ $qs(['type' => 'image']) }}">图片</a>
            <a class="chip {{ $type === 'file' ? 'active' : '' }}" href="{{ $qs(['type' => 'file']) }}">文件</a>
            @if($hasFilters)
                <a class="chip" href="{{ route('admin.media.index') }}">清除筛选</a>
            @endif
        </form>

        @if($media->isEmpty())
            <div class="list-empty">
                @if($hasFilters)
                    <p>没有符合条件的文件。</p>
                    <a class="btn btn-muted" href="{{ route('admin.media.index') }}">清除筛选</a>
                @else
                    <p>还没有文件。</p>
                    <p class="muted">拖到上方或点击上传。写内容时封面和正文里也可以直接传图。</p>
                @endif
            </div>
        @else
            <div class="media-lib-grid">
                @foreach($media as $item)
                    <article class="media-card">
                        @if($item->isImage())
                            <a class="media-card-thumb" href="{{ $item->url() }}" target="_blank" rel="noopener">
                                <img src="{{ $item->url() }}" alt="{{ $item->alt ?: $item->filename }}">
                            </a>
                        @else
                            <div class="media-card-file" aria-hidden="true"><i class="fas fa-file"></i></div>
                        @endif
                        <div class="media-card-body">
                            <div class="entry-row-title" title="{{ $item->filename }}">{{ $item->filename }}</div>
                            <div class="entry-row-meta">
                                {{ $fmtSize($item->size) }}
                                @if($item->alt && $item->alt !== pathinfo($item->filename, PATHINFO_FILENAME))
                                    · {{ $item->alt }}
                                @endif
                            </div>
                            <div class="media-card-actions">
                                <button type="button" class="btn btn-sm btn-muted" data-copy="{{ $item->url() }}">复制链接</button>
                                <a class="btn btn-sm btn-muted" href="{{ $item->url() }}" target="_blank" rel="noopener">打开</a>
                                <form action="{{ route('admin.media.destroy', $item) }}" method="post" onsubmit="return confirm(@json('确定删除「'.$item->filename.'」？内容里已插入的地址不会自动去掉。'))">
                                    @csrf @method('DELETE')
                                    <button class="btn-quiet" type="submit">删除</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="muted entry-index-count">共 {{ $media->total() }} 个</p>
            {{ $media->links() }}
        @endif
    </div>
@endsection
@push('scripts')
<script>
(function () {
    var form = document.getElementById('mediaUploadForm');
    var zone = document.getElementById('mediaDropZone');
    var input = document.getElementById('mediaFiles');
    if (!form || !zone || !input) return;
    zone.addEventListener('click', function () { input.click(); });
    zone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });
    input.addEventListener('change', function () {
        if (input.files && input.files.length) form.submit();
    });
    ;['dragenter', 'dragover'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('drag'); });
    });
    ;['dragleave', 'drop'].forEach(function (ev) {
        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('drag'); });
    });
    zone.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || !files.length) return;
        input.files = files;
        form.submit();
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (!btn) return;
        var url = btn.getAttribute('data-copy') || '';
        var done = function () {
            var old = btn.textContent;
            btn.textContent = '已复制';
            setTimeout(function () { btn.textContent = old; }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done).catch(function () { window.prompt('复制链接', url); });
        } else {
            window.prompt('复制链接', url);
        }
    });
})();
</script>
@endpush
