@extends('themes.default.layout')

@php
    use App\Support\Locale;
    $locale = Locale::current();
    $prefix = (! Locale::hideDefault() || $locale !== Locale::default())
        ? '/'.Locale::toPublic($locale)
        : '';
@endphp

@section('content')
    <h1>{{ $locale === 'en' ? 'Posts' : '文章' }}</h1>
    <ul class="post-list">
        @forelse($posts as $post)
            <li>
                <div class="meta">{{ optional($post->published_at)->format('Y-m-d') }} · {{ $post->category?->t('title') }}</div>
                <a href="{{ $prefix }}/posts/{{ $post->t('slug') }}"><strong>{{ $post->t('title') }}</strong></a>
                @if($post->t('excerpt'))
                    <p>{{ $post->t('excerpt') }}</p>
                @endif
            </li>
        @empty
            <li>{{ $locale === 'en' ? 'No posts yet.' : '暂无文章' }}</li>
        @endforelse
    </ul>
    {{ $posts->links() }}
@endsection
