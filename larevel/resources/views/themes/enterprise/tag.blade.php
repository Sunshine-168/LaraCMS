@extends('themes.enterprise.partials.layout')
@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    <div class="grid">
        @foreach($entries as $item)
            <article class="card-soft">
                <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            </article>
        @endforeach
    </div>
    {{ $entries->links() }}
@endsection
