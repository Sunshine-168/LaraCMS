@extends('cms_theme::layouts.app')

@section('content')
    @cmsBreadcrumb(['symbol' => ' / '])

    <article class="card">
        <h2 style="margin-top: 0;">{{ $article->title }}</h2>
        <div class="meta" style="margin-bottom: 16px;">
            @if($article->publish_time)
                发布时间：@cmsDate(['value' => $article->publish_time, 'format' => 'Y-m-d H:i'])
            @endif
            @if($article->author)
                · 作者：{{ $article->author }}
            @endif
            · 浏览：{{ $article->views }}
        </div>

        @if($article->subtitle)
            <p><strong>{{ $article->subtitle }}</strong></p>
        @endif

        @if($article->thumb)
            <p><img src="{{ $article->thumb }}" alt="{{ $article->title }}" style="max-width: 100%; border-radius: 8px;"></p>
        @endif

        <div class="content">{!! $content !!}</div>

        <div class="prev-next">
            <div>上一篇：@cmsPrev(['len' => 24, 'dot' => '...'])</div>
            <div>下一篇：@cmsNext(['len' => 24, 'dot' => '...'])</div>
        </div>
    </article>
@endsection
