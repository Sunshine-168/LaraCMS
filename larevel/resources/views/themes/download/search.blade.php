@extends('themes.download.partials.layout')
@section('content')
    <h1>搜索</h1>
    <form method="get" action="{{ route('search') }}" style="margin:16px 0">
        <input type="text" name="q" value="{{ $q }}" placeholder="关键词">
        <button class="btn" type="submit">搜索</button>
    </form>
    @foreach($results ?: [] as $item)
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
        </div>
    @endforeach
    @cmsPaginate
@endsection
