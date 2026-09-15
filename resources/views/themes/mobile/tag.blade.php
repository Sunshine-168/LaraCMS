@extends('themes.mobile.partials.layout')
@section('content')
    <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
    <div class="grid" style="margin-top:12px">
        @forelse($entries as $item)
            <a class="card-soft" href="{{ $item->url }}"><strong>{{ $item->t('title') }}</strong></a>
        @empty
            <p class="muted">{{ cms_ui('tag_empty') }}</p>
        @endforelse
    </div>
    <div style="margin:16px 0">{{ $entries->links('cms.partials.paginate') }}</div>
@endsection
