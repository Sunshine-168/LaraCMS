@extends('themes.blog.partials.layout')
@section('content')
    <p class="muted" style="margin-top:24px">简单博客主题 · 文章来自 article 模型</p>
    @cmsContent(['model'=>'article','num'=>10,'page'=>true,'order'=>'published_at desc'])
        <article>
            <div class="muted">@cmsDate(['name'=>$item->published_at])</div>
            <h2 style="margin:8px 0"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
            <p class="muted">@cmsSubstr(['name'=>$item->t('excerpt') ?: $item->t('content'),'len'=>140])</p>
        </article>
    @endcmsContent
    <div style="margin:24px 0">@cmsPaginate</div>
@endsection
