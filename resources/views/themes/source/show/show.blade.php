@extends('themes.source.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div class="card">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ cms_ui('license') }} {{ $entry->attr('license') }} / {{ $entry->attr('price') ?: cms_ui('free') }}</p>
        <div>{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p style="margin-top:16px">
            @if($entry->attr('demo_url'))<a href="{{ $entry->attr('demo_url') }}" target="_blank">{{ cms_ui('demo_online') }}</a> / @endif
            <a class="btn" href="{{ cms_route('download', $entry) }}">{{ cms_ui('download_pack') }}</a>
        </p>
        <p>{{ cms_ui('prev') }}：@cmsPrev / {{ cms_ui('next') }}：@cmsNext</p>
        @include('cms.partials.entry-tags')
    </div>

    @include('cms.comments')
@endsection
