@extends('cms.layout')

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('cms.home') }}">首页</a> / {{ $category->title }}
    </div>

    <section class="card">
        <h2 style="margin-top: 0;">{{ $category->title }}</h2>
        @if($category->seo_desc)
            <p class="meta">{{ $category->seo_desc }}</p>
        @endif

        @forelse($articles as $article)
            <article class="list-item">
                <h3 style="margin: 0 0 8px;">
                    <a href="{{ $article->url() }}">{{ $article->title }}</a>
                </h3>
                <div class="meta">
                    @if($article->publishTimeText())
                        发布时间：{{ $article->publishTimeText() }}
                    @endif
                    · 浏览：{{ $article->views }}
                </div>
                @if($article->description)
                    <p>{{ $article->description }}</p>
                @endif
            </article>
        @empty
            <p>当前栏目暂无内容。</p>
        @endforelse

        <div class="pagination">
            {{ $articles->links() }}
        </div>
    </section>
@endsection
