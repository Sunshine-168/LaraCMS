@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            @cmsBreadcrumb
            <p class="ig-kicker">{{ cms_t('方案', 'Packages') }}</p>
            <h1>{{ $category->t('title') }}</h1>
            <p class="ig-lead">{{ cms_t('三档按交付范围区分。报价和周期在沟通后另给。', 'The three packages differ by scope. Price and timing come after a conversation.') }}</p>
        </div>
    </header>
    <div class="ig-wrap bc-page">
        <div class="ig-packs">
            @cmsContent(['page' => true, 'num' => $category->page_size ?: 9])
                <a class="ig-pack" href="{{ $item->url }}">
                    @include('themes.bc.partials.shot', ['slug' => $item->t('slug'), 'alt' => $item->t('title')])
                    <div>
                        <h3>{{ $item->t('title') }}</h3>
                        <p>{{ $item->t('excerpt') }}</p>
                        <em>{{ cms_t('查看交付范围', 'See what is included') }}</em>
                    </div>
                </a>
            @endcmsContent
        </div>
        <div class="bc-pager">@cmsPaginate</div>
    </div>
@endsection
