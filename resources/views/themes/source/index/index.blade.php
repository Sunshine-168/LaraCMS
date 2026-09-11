@extends('themes.source.partials.layout')
@section('content')
    @include('cms.partials.front-hero-copy', [
        'fallbackTitle' => '开源 / 商业源码',
        'fallbackSub' => '演示、授权、打包下载一站搞定。',
    ])
    @cmsContent(['model'=>'source','num'=>12,'page'=>true])
        <div class="card">
            <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
            <p class="muted">{{ $item->attr('license') }} · {{ $item->attr('price') ?: '免费' }} · 下载 {{ $item->downloads_count }}</p>
            <p>
                @if($item->attr('demo_url'))<a href="{{ $item->attr('demo_url') }}" target="_blank">演示</a> · @endif
                <a class="btn" href="{{ cms_route('download', $item) }}">下载源码</a>
            </p>
        </div>
    @endcmsContent
    @cmsPaginate
@endsection
