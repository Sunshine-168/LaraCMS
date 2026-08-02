@extends('themes.nav.partials.layout')
@section('content')
    <h1>网址导航</h1>
    <p class="muted">精选常用站点，点击直达。</p>
    <section class="section">
        <h2>热门推荐</h2>
        <div class="grid">
            @cmsAdv(['name'=>'hot_links'])
                <a class="link" href="{{ $item->url }}" target="_blank" rel="noopener">
                    @if($item->image)<img src="{{ $item->image }}" alt="">@endif
                    <div>{{ $item->t('title') }}</div>
                </a>
            @endcmsAdv
        </div>
    </section>
    <section class="section">
        <h2>全部分类</h2>
        @cmsChannel(['type'=>'top','ismenu'=>1, 'as' => 'cat'])
            <h3 style="margin-top:20px">{{ $cat->t('title') }}</h3>
            <div class="grid">
                @cmsContent(['catid'=>$cat->id,'model'=>'link','num'=>24,'insub'=>false, 'as' => 'link'])
                    <a class="link" href="{{ route('go', $link) }}" target="_blank" rel="noopener">
                        @if($link->cover || $link->attr('icon'))
                            <img src="{{ $link->cover ?: $link->attr('icon') }}" alt="">
                        @endif
                        <div>{{ $link->t('title') }}</div>
                        <div class="muted">点击 {{ $link->clicks }}</div>
                    </a>
                @endcmsContent
            </div>
        @endcmsChannel
    </section>
@endsection
