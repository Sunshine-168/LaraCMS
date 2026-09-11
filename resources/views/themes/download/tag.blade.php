@extends('themes.download.partials.layout')
@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    @forelse($entries as $item)
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('platform') }} · {{ $item->attr('version') }}</p>
        </div>
    @empty
        <p class="muted">该标签下暂无内容。</p>
    @endforelse
    {{ $entries->links() }}
@endsection
