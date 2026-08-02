@extends('themes.default.layout')

@section('content')
    <article>
        <div class="meta">{{ optional($post->published_at)->format('Y-m-d') }} · {{ $post->category?->t('title') }}</div>
        <h1>{{ $post->t('title') }}</h1>
        <div class="content">{!! nl2br(e($post->t('content'))) !!}</div>
    </article>
@endsection
