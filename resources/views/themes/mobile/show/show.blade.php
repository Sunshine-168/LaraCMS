@extends('themes.mobile.partials.layout')
@section('content')
    @cmsBreadcrumb
    <article class="card-soft" style="margin-top:14px">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ cms_ui('views') }} {{ $entry->views }}</p>
        <div class="content" style="margin-top:12px">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p class="muted" style="margin-top:16px">{{ cms_ui('prev') }}：@cmsPrev · {{ cms_ui('next') }}：@cmsNext</p>
        @include('cms.partials.entry-tags')
    </article>

    @include('cms.comments')
@endsection
