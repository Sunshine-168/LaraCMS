@extends('themes.mobile.partials.layout')
@section('content')
    <h1>标签：{{ $tag->t('title') }}</h1>
    <div class="grid" style="margin-top:12px">
        @foreach($entries as $item)
            <a class="card-soft" href="{{ $item->url }}"><strong>{{ $item->t('title') }}</strong></a>
        @endforeach
    </div>
    <div style="margin:16px 0">{{ $entries->links() }}</div>
@endsection
