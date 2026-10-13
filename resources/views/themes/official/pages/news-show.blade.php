@extends('themes.official.partials.layout')

@section('title', cms_t($post['title_zh'], $post['title_en']).' — LeafCMS')
@section('description', cms_t($post['excerpt_zh'], $post['excerpt_en']))

@section('content')
    <div class="page-hero">
        <div class="wrap">
            <div class="kicker"><i></i> <a href="{{ cms_route('site.news') }}">{{ cms_t('动态', 'News') }}</a></div>
            <h1>{{ cms_t($post['title_zh'], $post['title_en']) }}</h1>
            <p class="lead">{{ $post['date'] }}</p>
        </div>
    </div>
    <section>
        <div class="wrap">
            <article class="card news-body">
                {!! cms_t($post['body_zh'], $post['body_en']) !!}
                @if ($post['slug'] === 'install')
                    <p><a class="btn btn-solid" href="{{ cms_route('site.guide') }}">{{ cms_t('查看安装', 'Install') }}</a></p>
                @endif
                @if ($post['slug'] === 'open-source')
                    <p><a class="btn btn-solid" href="{{ cms_route('site.open') }}">{{ cms_t('开源页', 'Open source') }}</a></p>
                @endif
            </article>
        </div>
    </section>
@endsection
