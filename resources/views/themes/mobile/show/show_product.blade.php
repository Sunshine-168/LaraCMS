@extends('themes.mobile.partials.layout')
@section('content')
    @cmsBreadcrumb
    <article class="card-soft" style="margin-top:14px">
        @if($entry->cover)<img src="{{ cms_media($entry->cover) }}" alt="{{ $entry->t('title') }}" style="width:100%;border-radius:8px;margin-bottom:12px">@endif
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ cms_ui('price') }}：{{ $entry->attr('price') ?: cms_ui('price_ask') }}</p>
        <div class="content">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        @if($entry->attr('buylink'))
            <p style="margin-top:14px"><a class="btn" href="{{ $entry->attr('buylink') }}" target="_blank">{{ cms_ui('inquire') }}</a></p>
        @endif
        @include('cms.partials.entry-tags')
    </article>

    @include('cms.comments')
@endsection
