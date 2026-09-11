@extends('themes.default.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => $site['title'] ?? config('app.name'),
        'fallbackSub' => $site['description'] ?? '',
    ])
    @cmsContent(['model' => 'article', 'num' => 8])
        <article class="card-soft">
            <div class="meta">@cmsDate(['name' => $item->published_at])</div>
            <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
            <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 140])</p>
        </article>
    @endcmsContent
@endsection
