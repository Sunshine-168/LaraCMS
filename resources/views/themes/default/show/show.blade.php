@extends('themes.default.layout')
@section('content')
    @cmsBreadcrumb
    <article>
        <h1>{{ $entry->t('title') }}</h1>
        <p class="meta">@cmsDate(['name' => $entry->published_at]) · {{ cms_ui('views') }} {{ $entry->views }}</p>
        <div class="content">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p class="muted" style="margin-top:28px">{{ cms_ui('prev') }}：@cmsPrev · {{ cms_ui('next') }}：@cmsNext</p>
    </article>
    @include('cms.comments')
@endsection
