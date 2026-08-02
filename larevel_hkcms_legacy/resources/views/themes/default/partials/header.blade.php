<header class="header">
    <div class="container">
        <h1>{{ $site['title'] ?? 'CMS' }}</h1>
        <nav>
            <a href="{{ route('cms.home') }}">首页</a>
            @cmsChannel(['type' => 'top', 'ismenu' => 1, 'num' => 12])
                <a href="{{ $item->url }}">{{ $item->title }}</a>
            @endcmsChannel
        </nav>
    </div>
</header>
