@extends('themes.manga.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $entry->t('title') }}</h1>
    <p class="muted"><a href="/comics">返回漫画库</a></p>
    <div class="pages" style="margin-top:16px">
        @foreach(preg_split('/\r\n|\r|\n/', (string)$entry->attr('pages')) as $img)
            @if(trim($img) !== '')
                <img src="{{ trim($img) }}" alt="" loading="lazy">
            @endif
        @endforeach
    </div>
    <p style="margin:20px 0">上一话：@cmsPrev · 下一话：@cmsNext</p>
@endsection
