@extends('themes.manga.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div style="display:grid;grid-template-columns:200px 1fr;gap:20px;margin-top:16px">
        <img src="{{ cms_media($entry->cover) }}" alt="" style="width:100%;border-radius:10px">
        <div>
            <h1>{{ $entry->t('title') }}</h1>
            <p class="muted">{{ $entry->attr('author') }} · {{ $entry->attr('area') }} · {{ $entry->attr('status') }}</p>
            <div>{!! $entry->linked_content ?? $entry->t('content') !!}</div>
            <h3 style="margin-top:20px">章节列表</h3>
            <ul style="padding-left:18px">
                @cmsContent(['model'=>'chapter','num'=>200,'order'=>'sort_order asc,id asc','related'=>$entry->id,'related_field'=>'manga_id'])
                    <li style="margin:8px 0"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></li>
                @endcmsContent
            </ul>
        </div>
    </div>

    @include('cms.comments')
@endsection
