@php
    $filterAllQuery = request()->except('filter');
    $filterAllHref = request()->url().($filterAllQuery ? '?'.http_build_query($filterAllQuery) : '');
    $filterAllActive = empty(request('filter'));
    $filterAllClass = $filterAllClass ?? '';
@endphp
<a href="{{ $filterAllHref }}" class="{{ trim($filterAllClass.' '.($filterAllActive ? 'active' : '')) }}">{{ cms_ui('all') }}</a>
