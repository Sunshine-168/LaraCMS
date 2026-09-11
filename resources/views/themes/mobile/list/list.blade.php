@extends('themes.mobile.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1 style="margin:14px 0">{{ $category->t('title') }}</h1>
    <div class="grid">
        @cmsContent(['catid' => $category->id, 'num' => 12, 'page' => true])
            <a class="card-soft" href="{{ $item->url }}">
                <strong>{{ $item->t('title') }}</strong>
                <div class="muted">{{ $item->t('excerpt') }}</div>
            </a>
        @endcmsContent
    </div>
    <div style="margin:16px 0">@cmsPaginate</div>
@endsection
