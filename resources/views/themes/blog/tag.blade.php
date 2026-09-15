@extends('themes.blog.partials.layout')
@section('content')
    <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
    @forelse($entries as $item)
        <article>
            <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
        </article>
    @empty
        <p class="muted">{{ cms_ui('tag_empty') }}</p>
    @endforelse
    {{ $entries->links('cms.partials.paginate') }}
@endsection
