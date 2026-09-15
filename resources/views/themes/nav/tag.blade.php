@extends('themes.nav.partials.layout')
@section('content')
    <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
    <div class="grid">
        @forelse($entries as $item)
            <a class="link" href="{{ $item->url }}">
                @if($item->cover || $item->attr('icon'))
                    <img src="{{ cms_media($item->cover ?: $item->attr('icon')) }}" alt="">
                @endif
                <div>{{ $item->t('title') }}</div>
            </a>
        @empty
            <p class="muted">{{ cms_ui('tag_empty') }}</p>
        @endforelse
    </div>
    {{ $entries->links('cms.partials.paginate') }}
@endsection
