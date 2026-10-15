@php
    $crumbCat = isset($category) ? $category->t('title') : '';
    $crumbLast = isset($entry) ? $entry->t('title') : '';
@endphp
<nav class="breadcrumb">
    <a href="{{ cms_front_url() }}">{{ cms_ui('home') }}</a>
    @if($crumbCat !== '')
        / <a href="{{ $category->frontUrl() }}">{{ $crumbCat }}</a>
    @endif
    @if($crumbLast !== '' && $crumbLast !== $crumbCat)
        / <span>{{ $crumbLast }}</span>
    @endif
</nav>
