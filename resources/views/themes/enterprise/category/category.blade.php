@extends('themes.enterprise.partials.layout')

@section('content')
    @cmsBreadcrumb
    <h1 style="margin:16px 0">{{ $category->t('title') }}</h1>
    <div class="grid">
        @cmsChannel(['type' => 'son', 'ismenu' => 1])
            <a class="card-soft" href="{{ $item->frontUrl() }}">
                <h3>{{ $item->t('title') }}</h3>
            </a>
        @endcmsChannel
    </div>
@endsection
