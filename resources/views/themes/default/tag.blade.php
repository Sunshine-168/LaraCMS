@extends('themes.default.layout')
@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    @forelse($entries as $item)
        <article>
            <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
        </article>
    @empty
        <p class="muted">该标签下暂无内容。</p>
    @endforelse
    {{ $entries->links() }}
@endsection
