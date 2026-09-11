@extends('themes.enterprise.partials.layout')

@section('content')
    @cmsBreadcrumb
    <article class="card-soft" style="margin-top:16px;display:grid;grid-template-columns:1fr 1.2fr;gap:20px">
        @if($entry->cover)<img src="{{ cms_media($entry->cover) }}" alt="" style="width:100%;border-radius:8px">@endif
        <div>
            <h1>{{ $entry->t('title') }}</h1>
            <p class="muted">价格：{{ $entry->attr('price') ?: '面议' }}</p>
            <div class="content">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
            @if($entry->attr('buylink'))
                <p><a class="btn" href="{{ $entry->attr('buylink') }}" target="_blank">立即咨询</a></p>
            @endif
        </div>
    </article>

    @include('cms.comments')
@endsection
