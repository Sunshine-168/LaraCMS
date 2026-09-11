@extends($layout)

@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    <div class="grid">
        @forelse($entries as $item)
            <article class="card-soft card">
                <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            </article>
        @empty
            <p class="muted">该标签下暂无内容。</p>
        @endforelse
    </div>
    {{ $entries->links() }}
@endsection
