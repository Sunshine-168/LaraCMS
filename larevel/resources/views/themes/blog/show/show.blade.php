@extends('themes.blog.partials.layout')
@section('content')
    @cmsBreadcrumb
    <article style="border:0">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">@cmsDate(['name'=>$entry->published_at]) · 浏览 {{ $entry->views }}</p>
        <div class="content" style="margin-top:20px">{!! nl2br(e($entry->t('content'))) !!}</div>
        <p class="muted" style="margin-top:28px">上一篇：@cmsPrev · 下一篇：@cmsNext</p>
    </article>

    @include('cms.comments')
@endsection
