@php
    $front = app(\App\Services\Cms\SiteFrontService::class);
    $contactUrl = $front->categoryBySlug('gw-contact')?->frontUrl() ?: cms_front_url('gw-contact');
    $isNote = $category->t('slug') === 'gw-insights';
    $more = $isNote
        ? app(\App\Services\Cms\Tags\ContentTag::class)->get(['model' => 'article', 'num' => 6, 'order' => 'published_at desc'])
        : collect();
@endphp
@extends('themes.bc.partials.layout')

@section('content')
    <article>
        <header class="ig-pagehead">
            <div class="ig-wrap">
                @include('themes.bc.partials.crumb')
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
                <p class="ig-date">@cmsDate(['name' => $entry->published_at])</p>
            @endif
            @php
                $body = $entry->linked_content ?? $entry->t('content');
                $localePrefix = \App\Support\UrlMode::localePrefix();
                if ($localePrefix !== '') {
                    $body = preg_replace('#href="(/gw-[^"]*)"#', 'href="'.$localePrefix.'$1"', (string) $body);
                }
            @endphp
            <div class="bc-prose">{!! $body !!}</div>
            @if($isNote)
                <div class="ig-more">
                    <h2>{{ cms_t('接着看', 'Related') }}</h2>
                    <div class="ig-news">
                        @foreach($more as $item)
                            @if($item->id !== $entry->id)
                                <a href="{{ $item->url }}">
                                    <time>@cmsDate(['name' => $item->published_at])</time>
                                    <div>
                                        <h3>{{ $item->t('title') }}</h3>
                                        <p>{{ $item->t('excerpt') }}</p>
                                    </div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                    <p class="ig-more-act"><a class="ig-btn" href="{{ $contactUrl }}">{{ cms_t('按这个方向咨询', 'Ask about this') }}</a></p>
                </div>
            @endif
        </div>
    </article>
@endsection
