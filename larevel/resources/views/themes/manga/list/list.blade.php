@extends('themes.manga.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    <div class="grid">
        @cmsContent(['page'=>true])
            <a class="card" href="{{ $item->url }}">
                <img src="{{ $item->cover ?: 'https://picsum.photos/seed/m'.$item->id.'/400/560' }}" alt="">
                <div class="body">
                    <strong>{{ $item->t('title') }}</strong>
                    <div class="muted">{{ $item->attr('status') }}</div>
                </div>
            </a>
        @endcmsContent
    </div>
    @cmsPaginate
@endsection
