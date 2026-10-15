@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            @include('themes.bc.partials.crumb')
            <h1>{{ $category->t('title') }}</h1>
        </div>
    </header>
    <div class="ig-wrap bc-page">
        <div class="ig-news">
            @cmsContent(['page' => true, 'num' => $category->page_size ?: 9])
                <a href="{{ $item->url }}">
                    <time>@cmsDate(['name' => $item->published_at])</time>
                    <div>
                        <h3>{{ $item->t('title') }}</h3>
                        <p>{{ $item->t('excerpt') }}</p>
                    </div>
                </a>
            @endcmsContent
        </div>
        <div class="bc-pager">@cmsPaginate</div>
    </div>
@endsection
