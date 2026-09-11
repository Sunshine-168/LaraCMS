@extends('themes.download.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div class="card">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">平台 {{ $entry->attr('platform') }} / 版本 {{ $entry->attr('version') }} / 大小 {{ $entry->attr('size') }}</p>
        <div>{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <h3 style="margin-top:20px">附件</h3>
        @cmsFileinfo(['field' => ['file', 'package']])
            <p class="muted">{{ $item->filename }} / {{ $item->size_text }}
                <a class="btn" href="{{ $item->download_url }}">下载</a>
            </p>
        @endcmsFileinfo
        <p style="margin-top:16px"><a class="btn" href="{{ route('download', $entry) }}">立即下载 ({{ $entry->downloads_count }})</a></p>
    </div>

    @include('cms.comments')
@endsection
