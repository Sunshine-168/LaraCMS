@extends('cms_theme::layouts.app')

@section('content')
    @cmsBreadcrumb(['symbol' => ' / '])

    <section class="card" style="margin-bottom: 24px;">
        <h2 style="margin-top: 0;">{{ $category->title }}</h2>
        @if($category->seo_desc)
            <p class="meta">{{ $category->seo_desc }}</p>
        @endif
    </section>

    <div class="grid grid-3" style="margin-bottom: 24px;">
        @cmsChannel(['type' => 'son', 'ismenu' => 1])
            <article class="card">
                <h3 style="margin-top: 0;">
                    <a href="{{ $item->url }}">{{ $item->title }}</a>
                </h3>
                <p class="meta">{{ $item->seo_desc ?: '进入子栏目' }}</p>
            </article>
        @endcmsChannel
    </div>

    <section class="card">
        <h2 style="margin-top: 0;">栏目内容</h2>
        @cmsContent(['num' => $category->num ?: 10, 'page' => true, 'order' => 'weigh desc,publish_time desc,id desc'])
            <article class="list-item">
                <h3 style="margin: 0 0 8px;">
                    <a href="{{ $item->url }}">{{ $item->title }}</a>
                </h3>
                <div class="meta">
                    发布时间：@cmsDate(['value' => $item->publish_time, 'format' => 'Y-m-d'])
                    · 浏览：{{ $item->views }}
                </div>
            </article>
        @endcmsContent

        @cmsPaginate
    </section>
@endsection
