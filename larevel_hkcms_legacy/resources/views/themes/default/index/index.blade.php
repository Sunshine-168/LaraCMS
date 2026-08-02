@extends('cms_theme::layouts.app')

@section('content')
    <div class="grid" style="margin-bottom: 24px;">
        <section class="card">
            <h2 style="margin-top: 0;">站点首页</h2>
            <p class="meta">{{ $site['description'] ?? '基于 Laravel Blade 指令标签的现代 CMS 前台。' }}</p>
        </section>
    </div>

    <section class="card" style="margin-bottom: 24px;">
        <h2 style="margin-top: 0;">推荐位</h2>
        @cmsAdv(['name' => 'home_banner', 'num' => 5, 'as' => 'banner'])
            <article class="list-item">
                @if(is_array($banner))
                    <h3 style="margin: 0 0 8px;">
                        <a href="{{ $banner['url'] ?? '#' }}">{{ $banner['title'] ?? '推荐内容' }}</a>
                    </h3>
                @else
                    <h3 style="margin: 0 0 8px;">
                        <a href="{{ $banner->url ?? '#' }}">{{ $banner->title ?? '推荐内容' }}</a>
                    </h3>
                    @if(!empty($banner->image))
                        <p><img src="{{ $banner->image }}" alt="{{ $banner->title }}" style="max-width:100%;border-radius:8px;"></p>
                    @endif
                @endif
            </article>
        @endcmsAdv
    </section>

    <div class="grid grid-3" style="margin-bottom: 24px;">
        @cmsChannel(['type' => 'top', 'ismenu' => 1, 'num' => 9])
            <article class="card">
                <h3 style="margin-top: 0;">
                    <a href="{{ $item->url }}">{{ $item->title }}</a>
                </h3>
                <p class="meta">{{ $item->seo_desc ?: '查看该栏目内容' }}</p>
            </article>
        @endcmsChannel
    </div>

    <section class="card">
        <h2 style="margin-top: 0;">最新内容</h2>
        @cmsContent(['num' => 12, 'order' => 'weigh desc,publish_time desc,id desc', 'insub' => false])
            <article class="list-item">
                <h3 style="margin: 0 0 8px;">
                    <a href="{{ $item->url }}">@cmsSubstr(['value' => $item->title, 'len' => 80, 'dot' => '...'])</a>
                </h3>
                <div class="meta">
                    栏目：{{ $item->category?->title ?? '未分类' }}
                    · 发布时间：@cmsDate(['value' => $item->publish_time, 'format' => 'Y-m-d H:i'])
                    · 浏览：{{ $item->views }}
                </div>
                @if($item->description)
                    <p>@cmsSubstr(['value' => $item->description, 'len' => 120, 'dot' => '...'])</p>
                @endif
            </article>
        @endcmsContent
    </section>
@endsection
