@extends('themes.enterprise.partials.layout')
@section('content')
    <h1>{{ cms_ui('search') }}</h1>
    @include('cms.partials.search-form')
    @if(($q ?? '') !== '')
        <p class="muted">{{ cms_ui('keywords') }}：{{ $q }}</p>
        <div class="grid">
            @forelse($results as $item)
                <article class="card-soft">
                    <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                    <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 100])</p>
                </article>
            @empty
                <p>{{ cms_ui('no_results') }}</p>
            @endforelse
        </div>
        <div style="margin-top:16px">@cmsPaginate</div>
    @endif
@endsection
