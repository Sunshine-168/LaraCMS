@extends('themes.bc.partials.layout')

@section('content')
    <article>
        <header class="ig-pagehead">
            <div class="ig-wrap">
                @cmsBreadcrumb
                <h1>{{ $entry->t('title') }}</h1>
                @if($entry->t('excerpt'))
                    <p class="ig-lead">{{ $entry->t('excerpt') }}</p>
                @endif
            </div>
        </header>
        <div class="ig-wrap bc-page">
            @if($category->t('slug') === 'gw-api')
                <div class="ig-hero-inline">@include('themes.bc.partials.cats')</div>
            @endif
            @if($category->type !== 'single')
                <p class="breadcrumb">@cmsDate(['name' => $entry->published_at])</p>
            @endif
            <div class="bc-prose">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        </div>
    </article>
@endsection
