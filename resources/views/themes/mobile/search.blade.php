@extends('themes.mobile.partials.layout')
@section('content')
    <h1>搜索</h1>
    @include('cms.partials.search-form')
    <div class="grid">
        @forelse(($results ?? []) as $item)
            <a class="card-soft" href="{{ $item->url }}"><strong>{{ $item->t('title') }}</strong></a>
        @empty
            @if(($q ?? '') !== '')
                <p class="muted">暂无结果</p>
            @endif
        @endforelse
    </div>
    @if(isset($results) && method_exists($results, 'links'))
        <div style="margin-top:12px">@cmsPaginate</div>
    @endif
@endsection
