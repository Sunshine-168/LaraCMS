@extends('themes.enterprise.partials.layout')

@section('content')
    @cmsBreadcrumb
    <h1 style="margin:16px 0">{{ $category->t('title') }}</h1>
    <div class="grid">
        @cmsContent(['page' => true, 'num' => $category->page_size ?: 10])
            <article class="card-soft">
                <div class="muted">@cmsDate(['name' => $item->published_at])</div>
                <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 100])</p>
            </article>
        @endcmsContent
    </div>
    <div style="margin:20px 0">@cmsPaginate</div>
@endsection
