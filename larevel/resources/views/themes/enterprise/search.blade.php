@extends('themes.enterprise.partials.layout')
@section('content')
    <h1>搜索</h1>
    <form method="get" action="{{ route('search') }}" style="margin:16px 0">
        <input type="text" name="q" value="{{ $q }}" placeholder="关键词" style="padding:8px;width:60%;max-width:420px">
        <button class="btn" type="submit">搜索</button>
    </form>
    @if($q !== '')
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
