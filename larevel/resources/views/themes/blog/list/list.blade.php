@extends('themes.blog.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1 style="margin-top:20px">{{ $category->t('title') }}</h1>
    @cmsContent(['page'=>true,'order'=>'published_at desc'])
        <article>
            <div class="muted">@cmsDate(['name'=>$item->published_at])</div>
            <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
            <p class="muted">@cmsSubstr(['name'=>$item->t('excerpt') ?: $item->t('content'),'len'=>120])</p>
        </article>
    @endcmsContent
    @cmsPaginate
@endsection
