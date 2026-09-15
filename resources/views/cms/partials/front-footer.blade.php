<footer>
    <div class="wrap">
        @if(!empty($prefix)){{ $prefix }}@endif&copy; {{ date('Y') }} {{ $site['title'] ?? config('app.name') }}
        @if(!empty($site['footer_text']))
            <div>{{ $site['footer_text'] }}</div>
        @endif
        @if(!empty($site['icp']))
            <div><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow">{{ $site['icp'] }}</a></div>
        @endif
        @if(app(\App\Services\Cms\SitemapService::class)->htmlEnabled())
            <div><a href="{{ cms_front_url('sitemap') }}" rel="noopener">{{ cms_ui('sitemap') }}</a></div>
        @endif
    </div>
</footer>
{!! $site['analytics_code'] ?? '' !!}
