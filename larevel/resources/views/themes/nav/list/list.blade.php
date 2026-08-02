@extends('themes.nav.partials.layout')
@section('content')
    @cmsBreadcrumb
    <h1>{{ $category->t('title') }}</h1>
    <div class="grid">
        @cmsContent(['page'=>true,'num'=>48])
            <a class="link" href="{{ route('go', $item) }}" target="_blank" rel="noopener">
                @if($item->attr('icon'))<img src="{{ $item->attr('icon') }}" alt="">@endif
                <div>{{ $item->t('title') }}</div>
            </a>
        @endcmsContent
    </div>
    @cmsPaginate
@endsection
