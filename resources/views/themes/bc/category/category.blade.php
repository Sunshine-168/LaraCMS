@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            @include('themes.bc.partials.crumb')
            <h1>{{ $category->t('title') }}</h1>
        </div>
    </header>
    <div class="ig-wrap bc-page">
        <div class="ig-packs">
            @cmsChannel(['type' => 'son', 'ismenu' => 1])
                <a class="ig-pack" href="{{ $item->frontUrl() }}"><div><h3>{{ $item->t('title') }}</h3></div></a>
            @endcmsChannel
        </div>
    </div>
@endsection
