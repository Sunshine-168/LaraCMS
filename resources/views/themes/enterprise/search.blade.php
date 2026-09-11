@extends('themes.enterprise.partials.layout')
@section('content')
    <h1>搜索</h1>
    @include('cms.partials.search-form')
    @if(($q ?? '') !== '')
        <p class="muted">关键词：{{ $q }}</p>
        <div class="grid">
            @forelse($results as $item)
                <article class="card-soft">
                    <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                    <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 100])</p>
                </article>
            @empty
                <p>没有结果</p>
            @endforelse
        </div>
        <div style="margin-top:16px">@cmsPaginate</div>
    @endif
@endsection
