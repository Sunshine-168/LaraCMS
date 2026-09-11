@extends('themes.default.layout')
@section('content')
    @cmsBreadcrumb
    <h1 style="margin-top:8px">{{ $category->t('title') }}</h1>
    <ul class="post-list">
        @cmsContent(['page' => true, 'order' => 'published_at desc'])
            <li>
                <div class="meta">@cmsDate(['name' => $item->published_at])</div>
                <h2><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h2>
                <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 140])</p>
            </li>
        @endcmsContent
    </ul>
    @cmsPaginate
@endsection
