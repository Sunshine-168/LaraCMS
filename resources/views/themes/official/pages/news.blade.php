@extends('themes.official.partials.layout')

@section('title', cms_t('动态 — LeafCMS', 'News — LeafCMS'))
@section('description', cms_t('LeafCMS 的开源、安装和飞机联系。', 'LeafCMS open source, install notes, and Telegram.'))

@section('content')
    <div class="page-hero">
        <div class="wrap">
            <div class="kicker"><i></i> News</div>
            <h1>{{ cms_t('动态', 'News') }}</h1>
            <p class="lead">{{ cms_t('开源、安装和飞机联系，记在这里。', 'Open source, install notes, and Telegram live here.') }}</p>
        </div>
    </div>
    <section>
        <div class="wrap news-list">
            @foreach (\App\Support\OfficialNews::all() as $post)
                <article class="card">
                    <p class="note">{{ $post['date'] }}</p>
                    <h3><a href="{{ cms_route('site.news.show', ['slug' => $post['slug']]) }}">{{ cms_t($post['title_zh'], $post['title_en']) }}</a></h3>
                    <p>{{ cms_t($post['excerpt_zh'], $post['excerpt_en']) }}</p>
                    <p><a href="{{ cms_route('site.news.show', ['slug' => $post['slug']]) }}">{{ cms_t('阅读', 'Read') }}</a></p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
