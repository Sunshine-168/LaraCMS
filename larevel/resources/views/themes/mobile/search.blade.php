@extends('themes.mobile.partials.layout')
@section('content')
    <h1>搜索</h1>
    <form method="get" action="{{ route('search') }}" style="margin:12px 0">
        <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="关键词" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    </form>
    <div class="grid">
        @forelse(($results ?? []) as $item)
            <a class="card-soft" href="{{ $item->url }}"><strong>{{ $item->t('title') }}</strong></a>
        @empty
            <p class="muted">暂无结果</p>
        @endforelse
    </div>
@endsection
