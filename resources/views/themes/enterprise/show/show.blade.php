@extends('themes.enterprise.partials.layout')

@section('content')
    @cmsBreadcrumb
    <article style="margin-top:16px">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">@cmsDate(['name' => $entry->published_at]) · {{ cms_ui('views') }} {{ $entry->views }}</p>
        <div class="content" style="margin-top:16px">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p style="margin-top:24px">{{ cms_ui('prev') }}：@cmsPrev · {{ cms_ui('next') }}：@cmsNext</p>
        @include('cms.partials.entry-tags')
    </article>

    @include('cms.comments')
@endsection
