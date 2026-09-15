@extends('themes.download.partials.layout')
@section('content')
    <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
    @forelse($entries as $item)
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('platform') }} · {{ $item->attr('version') }}</p>
        </div>
    @empty
        <p class="muted">{{ cms_ui('tag_empty') }}</p>
    @endforelse
    {{ $entries->links('cms.partials.paginate') }}
@endsection
