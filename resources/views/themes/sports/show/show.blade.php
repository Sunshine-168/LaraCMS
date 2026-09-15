@extends('themes.sports.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div class="card">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ $entry->attr('sport') }} / {{ $entry->attr('kickoff') }} / {{ $entry->attr('quality') }}</p>
        <div>{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p style="margin-top:16px"><a class="btn" href="{{ cms_route('go', $entry) }}" target="_blank">{{ cms_ui('open_signal') }}</a></p>
        <p class="muted" style="margin-top:16px">{{ cms_ui('prev') }}：@cmsPrev · {{ cms_ui('next') }}：@cmsNext</p>
        @include('cms.partials.entry-tags')
    </div>

    @include('cms.comments')
@endsection
