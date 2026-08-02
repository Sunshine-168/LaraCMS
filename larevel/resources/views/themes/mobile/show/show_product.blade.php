@extends('themes.mobile.partials.layout')
@section('content')
    @cmsBreadcrumb
    <article class="card-soft" style="margin-top:14px">
        @if($entry->cover)<img src="{{ $entry->cover }}" alt="" style="width:100%;border-radius:8px;margin-bottom:12px">@endif
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">价格：{{ $entry->attr('price') ?: '面议' }}</p>
        <div class="content">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        @if($entry->attr('buylink'))
            <p style="margin-top:14px"><a class="btn" href="{{ $entry->attr('buylink') }}" target="_blank">立即咨询</a></p>
        @endif
    </article>
@endsection
