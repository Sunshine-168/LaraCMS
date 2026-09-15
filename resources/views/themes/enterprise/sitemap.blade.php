{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as $url)
    @php $item = is_array($url) ? $url : ['loc' => $url]; @endphp
    <url>
        <loc>{{ $item['loc'] }}</loc>
        @if(!empty($item['lastmod']))
        <lastmod>{{ $item['lastmod'] }}</lastmod>
        @endif
        @if(!empty($item['changefreq']))
        <changefreq>{{ $item['changefreq'] }}</changefreq>
        @endif
        @if(!empty($item['priority']))
        <priority>{{ $item['priority'] }}</priority>
        @endif
    </url>
@endforeach
</urlset>
