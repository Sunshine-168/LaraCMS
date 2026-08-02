@extends('themes.mobile.partials.layout')
@section('content')
    @cmsBreadcrumb
    <article class="card-soft" style="margin-top:14px">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">浏览 {{ $entry->views }}</p>
        <div class="content" style="margin-top:12px">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
    </article>
@endsection
