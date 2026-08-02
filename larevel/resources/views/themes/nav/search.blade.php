@extends('themes.nav.partials.layout')
@section('content')
    <h1>搜索</h1>
    <form method="get" action="{{ route('search') }}">
        <input type="text" name="q" value="{{ $q }}">
        <button type="submit">搜</button>
    </form>
    <div class="grid" style="margin-top:16px">
        @foreach($results ?: [] as $item)
            <a class="link" href="{{ $item->url }}">{{ $item->t('title') }}</a>
        @endforeach
    </div>
@endsection
