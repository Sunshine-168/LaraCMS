@extends('cms.layout')

@section('content')
    <div class="grid" style="margin-bottom: 24px;">
        <section class="card">
            <h2 style="margin-top: 0;">站点首页</h2>
            <p class="meta">这里是从旧 HkCms 搬过来的 Laravel 前台起点，当前已接入站点配置、栏目和内容表。</p>
        </section>
    </div>

    <div class="grid grid-3" style="margin-bottom: 24px;">
        @foreach($categories as $category)
            <article class="card">
                <h3 style="margin-top: 0;">
                    <a href="{{ $category->url() }}">{{ $category->title }}</a>
                </h3>
                <p class="meta">{{ $category->seo_desc ?: '查看该栏目内容' }}</p>
            </article>
        @endforeach
    </div>

    <section class="card">
        <h2 style="margin-top: 0;">最新内容</h2>
        @forelse($articles as $article)
            <article class="list-item">
                <h3 style="margin: 0 0 8px;">
                    <a href="{{ $article->url() }}">{{ $article->title }}</a>
                </h3>
                <div class="meta">
                    栏目：{{ $article->category?->title ?? '未分类' }}
                    @if($article->publishTimeText())
                        · 发布时间：{{ $article->publishTimeText() }}
                    @endif
                    · 浏览：{{ $article->views }}
                </div>
                @if($article->description)
                    <p>{{ $article->description }}</p>
                @endif
            </article>
        @empty
            <p>暂无内容。</p>
        @endforelse
    </section>
@endsection
