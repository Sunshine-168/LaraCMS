@extends('themes.source.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => cms_ui('source_hero'),
        'fallbackSub' => cms_ui('source_hero_sub'),
    ])
    @cmsContent(['model'=>'source','num'=>12,'page'=>true])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('license') }} · {{ $item->attr('price') ?: cms_ui('free') }} · {{ cms_ui('download') }} {{ $item->downloads_count }}</p>
            <p>
                @if($item->attr('demo_url'))<a href="{{ $item->attr('demo_url') }}" target="_blank">{{ cms_ui('demo') }}</a> · @endif
                <a class="btn" href="{{ cms_route('download', $item) }}">{{ cms_ui('download_source') }}</a>
            </p>
        </div>
    @endcmsContent
    @cmsPaginate
    @include('cms.partials.friend-links')
@endsection
