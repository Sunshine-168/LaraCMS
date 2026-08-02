@extends('cms_theme::layouts.app')

@section('content')
    @cmsBreadcrumb(['symbol' => ' / '])

    <section class="card">
        <h2 style="margin-top: 0;">{{ $category->title }}</h2>
        @if($category->seo_desc)
            <p class="meta">{{ $category->seo_desc }}</p>
        @endif

        @cmsContent(['num' => $category->num ?: 10, 'page' => true, 'order' => 'weigh desc,publish_time desc,id desc'])
            <article class="list-item">
                <h3 style="margin: 0 0 8px;">
                    <a href="{{ $item->url }}">{{ $item->title }}</a>
                </h3>
                <div class="meta">
                    发布时间：@cmsDate(['value' => $item->publish_time, 'format' => 'Y-m-d H:i'])
                    · 浏览：{{ $item->views }}
                </div>
                @if($item->description)
                    <p>{{ $item->description }}</p>
                @endif
            </article>
        @endcmsContent

        @cmsPaginate
    </section>
@endsection
