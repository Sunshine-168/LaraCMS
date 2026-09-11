@extends('themes.blog.partials.layout')
@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    @foreach($entries as $item)
        <article>
            <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
        </article>
    @endforeach
    {{ $entries->links() }}
@endsection
