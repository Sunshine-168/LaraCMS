@extends('themes.enterprise.partials.layout')

@section('content')
    @cmsBreadcrumb
    <article style="margin-top:16px">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">@cmsDate(['name' => $entry->published_at]) · 浏览 {{ $entry->views }}</p>
        <div class="content" style="margin-top:16px">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p style="margin-top:24px">上一篇：@cmsPrev · 下一篇：@cmsNext</p>
    </article>
@endsection
