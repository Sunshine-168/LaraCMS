@extends('themes.default.layout')
@section('content')
    <h1>{{ \App\Support\Locale::isEnglish() ? 'Search' : '搜索' }}</h1>
    @include('cms.partials.search-form')
    @if(($q ?? '') !== '')
        <p class="muted">{{ \App\Support\Locale::isEnglish() ? 'Keywords' : '关键词' }}：{{ $q }}</p>
        @forelse($results as $item)
            <article class="card-soft">
                <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
                <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 120])</p>
            </article>
        @empty
            <p>{{ \App\Support\Locale::isEnglish() ? 'No results' : '没有结果' }}</p>
        @endforelse
        <div style="margin-top:16px">@cmsPaginate</div>
    @endif
@endsection
