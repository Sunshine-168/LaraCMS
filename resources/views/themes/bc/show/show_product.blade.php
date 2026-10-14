@php
    $contactUrl = app(\App\Services\Cms\SiteFrontService::class)->categoryBySlug('gw-contact')?->frontUrl() ?: cms_front_url('gw-contact');
@endphp
@extends('themes.bc.partials.layout')

@section('content')
    <article>
        <header class="ig-pagehead">
            <div class="ig-wrap">
                @cmsBreadcrumb
                <p class="ig-kicker">{{ cms_t('方案', 'Package') }}</p>
                <h1>{{ $entry->t('title') }}</h1>
                @if($entry->t('excerpt'))
                    <p class="ig-lead">{{ $entry->t('excerpt') }}</p>
                @endif
            </div>
        </header>
        <div class="ig-wrap bc-page">
            <div class="ig-hero-inline">
                @include('themes.bc.partials.shot', ['slug' => $entry->t('slug'), 'alt' => $entry->t('title')])
            </div>
            <div class="bc-prose">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
            <p><a class="ig-btn" href="{{ $contactUrl }}">{{ cms_t('按这一档咨询', 'Ask about this package') }}</a></p>
        </div>
    </article>
@endsection
