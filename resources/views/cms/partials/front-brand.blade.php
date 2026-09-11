<a class="brand" href="{{ cms_front_url() }}">
    @if(!empty($site['logo_url']))
        <img src="{{ $site['logo_url'] }}" alt="{{ $site['title'] ?? ($fallback ?? config('app.name')) }}">
    @endif
    <span>{{ $site['title'] ?? ($fallback ?? config('app.name')) }}</span>
</a>
