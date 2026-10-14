@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
        </div>
    </header>
    <div class="ig-wrap bc-page">
        <div class="ig-news">
            @forelse($entries as $item)
                <a href="{{ $item->url }}">
                    <h3>{{ $item->t('title') }}</h3>
                </a>
            @empty
                <p>{{ cms_ui('tag_empty') }}</p>
            @endforelse
        </div>
        <div class="bc-pager">{{ $entries->links('cms.partials.paginate') }}</div>
    </div>
@endsection
