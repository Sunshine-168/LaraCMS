@extends('themes.manga.partials.layout')
@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    <div class="grid">
        @forelse($entries as $item)
            <a class="card" href="{{ $item->url }}">
                <img src="{{ cms_media($item->cover) ?: 'https://picsum.photos/seed/t'.$item->id.'/400/560' }}" alt="">
                <div class="body">
                    <strong>{{ $item->t('title') }}</strong>
                </div>
            </a>
        @empty
            <p class="muted">该标签下暂无内容。</p>
        @endforelse
    </div>
    {{ $entries->links() }}
@endsection
