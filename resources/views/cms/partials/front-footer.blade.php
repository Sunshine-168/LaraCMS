<footer>
    <div class="wrap">
        @if(!empty($prefix)){{ $prefix }}@endif&copy; {{ date('Y') }} {{ $site['title'] ?? config('app.name') }}
        @if(!empty($site['footer_text']))
            <div>{{ $site['footer_text'] }}</div>
        @endif
        @if(!empty($site['icp']))
            <div><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow">{{ $site['icp'] }}</a></div>
        @endif
    </div>
</footer>
{!! $site['analytics_code'] ?? '' !!}
