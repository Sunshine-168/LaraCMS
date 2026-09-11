@extends('themes.source.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div class="card">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">授权 {{ $entry->attr('license') }} / {{ $entry->attr('price') ?: '免费' }}</p>
        <div>{!! nl2br(e($entry->t('content'))) !!}</div>
        <p style="margin-top:16px">
            @if($entry->attr('demo_url'))<a href="{{ $entry->attr('demo_url') }}" target="_blank">在线演示</a> / @endif
            <a class="btn" href="{{ route('download', $entry) }}">下载源码包</a>
        </p>
        <p>上一篇：@cmsPrev / 下一篇：@cmsNext</p>
    </div>

    @include('cms.comments')
@endsection
