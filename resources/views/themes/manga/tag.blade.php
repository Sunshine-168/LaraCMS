@extends('themes.manga.partials.layout')
@section('content')
    <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
    <div class="grid">
        @forelse($entries as $item)
            <a class="card" href="{{ $item->url }}">
                @if($item->cover)
                    <img src="{{ cms_media($item->cover) }}" alt="">
                @endif
                <div class="body">
                    <strong>{{ $item->t('title') }}</strong>
                </div>
            </a>
        @empty
            <p class="muted">{{ cms_ui('tag_empty') }}</p>
        @endforelse
    </div>
    {{ $entries->links('cms.partials.paginate') }}
@endsection
