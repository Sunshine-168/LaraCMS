@extends('cms.layout')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('cms.home') }}">首页</a> /
        <a href="{{ $category->url() }}">{{ $category->title }}</a> /
        {{ $article->title }}
    </div>

    <article class="card">
        <h2 style="margin-top: 0;">{{ $article->title }}</h2>
        <div class="meta" style="margin-bottom: 16px;">
            @if($article->publishTimeText())
                发布时间：{{ $article->publishTimeText() }}
            @endif
            @if($article->author)
                · 作者：{{ $article->author }}
            @endif
            · 浏览：{{ $article->views + 1 }}
        </div>

        @if($article->subtitle)
            <p><strong>{{ $article->subtitle }}</strong></p>
        @endif

        @if($article->thumb)
            <p><img src="{{ $article->thumb }}" alt="{{ $article->title }}" style="max-width: 100%; border-radius: 8px;"></p>
        @endif

        <div class="content">{!! $content !!}</div>
    </article>
@endsection
