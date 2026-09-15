@extends($layout)

@section('content')
    <h1>{{ cms_ui('tag') }}：{{ $tag->t('title') }}</h1>
    <div class="grid">
        @forelse($entries as $item)
            <article class="card-soft card">
                <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            </article>
        @empty
            <p class="muted">{{ cms_ui('tag_empty') }}</p>
        @endforelse
    </div>
    {{ $entries->links('cms.partials.paginate') }}
@endsection
