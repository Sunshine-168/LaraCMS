@extends('themes.download.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div class="card">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ cms_ui('platform') }} {{ $entry->attr('platform') }} / {{ cms_ui('version') }} {{ $entry->attr('version') }} / {{ cms_ui('size') }} {{ $entry->attr('size') }}</p>
        <div>{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        @php $files = app(\App\Services\Cms\Tags\FileinfoTag::class)->get(['field' => ['file', 'package']]); @endphp
        @if($files->isNotEmpty())
            <h3 style="margin-top:20px">{{ cms_ui('attachments') }}</h3>
            @foreach($files as $item)
                <p class="muted">{{ $item->filename }} / {{ $item->size_text }}
                    <a class="btn" href="{{ $item->download_url }}">{{ cms_ui('download') }}</a>
                </p>
            @endforeach
        @endif
        <p style="margin-top:16px"><a class="btn" href="{{ cms_route('download', $entry) }}">{{ cms_ui('download_now') }} ({{ $entry->downloads_count }})</a></p>
        <p class="muted" style="margin-top:16px">{{ cms_ui('prev') }}：@cmsPrev · {{ cms_ui('next') }}：@cmsNext</p>
        @include('cms.partials.entry-tags')
    </div>

    @include('cms.comments')
@endsection
