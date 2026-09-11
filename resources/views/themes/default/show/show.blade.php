@extends('themes.default.layout')
@section('content')
    @cmsBreadcrumb
    <article>
        <h1>{{ $entry->t('title') }}</h1>
        <p class="meta">@cmsDate(['name' => $entry->published_at]) · {{ \App\Support\Locale::isEnglish() ? 'Views' : '浏览' }} {{ $entry->views }}</p>
        <div class="content">{!! $entry->linked_content ?? $entry->t('content') !!}</div>
        <p class="muted" style="margin-top:28px">{{ \App\Support\Locale::isEnglish() ? 'Previous' : '上一篇' }}：@cmsPrev · {{ \App\Support\Locale::isEnglish() ? 'Next' : '下一篇' }}：@cmsNext</p>
    </article>
    @include('cms.comments')
@endsection
