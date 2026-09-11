@extends('themes.default.layout')

@section('content')
    <article>
        <h1>{{ $page->t('title') }}</h1>
        <div class="content">{!! nl2br(e($page->t('content'))) !!}</div>
    </article>
@endsection
