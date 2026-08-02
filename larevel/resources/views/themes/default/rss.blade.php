{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0">
<channel>
    <title>{{ $site['title'] ?? config('app.name') }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ $site['description'] ?? '' }}</description>
    @foreach($entries as $entry)
    <item>
        <title>{{ $entry->t('title') }}</title>
        <link>{{ url($entry->url) }}</link>
        <guid>{{ url($entry->url) }}</guid>
        <pubDate>{{ optional($entry->published_at)->toRssString() }}</pubDate>
        <description><![CDATA[{{ $entry->t('excerpt') ?: \Illuminate\Support\Str::limit(strip_tags($entry->t('content')), 200) }}]]></description>
    </item>
    @endforeach
</channel>
</rss>
