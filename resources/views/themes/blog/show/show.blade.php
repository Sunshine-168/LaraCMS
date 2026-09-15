@extends('themes.blog.partials.layout')
@section('content')
    @cmsBreadcrumb
    <article style="border:0">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">@cmsDate(['name'=>$entry->published_at]) · {{ cms_ui('views') }} {{ $entry->views }}</p>
        <div class="content" style="margin-top:20px">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p class="muted" style="margin-top:28px">{{ cms_ui('prev') }}：@cmsPrev · {{ cms_ui('next') }}：@cmsNext</p>
    </article>

    @include('cms.comments')
@endsection
