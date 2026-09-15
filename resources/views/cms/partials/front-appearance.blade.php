@if(!empty($site['favicon_url']))
    <link rel="icon" href="{{ $site['favicon_url'] }}">
@endif
<style>
    .brand{display:inline-flex;align-items:center;gap:10px}
    .brand img{max-height:36px;width:auto;display:block}
    footer{margin-top:36px;padding:24px 0;font-size:13px}
    .lang a[aria-current="page"]{font-weight:700;text-decoration:none;pointer-events:none;opacity:.88}
</style>
@if(!empty($site['primary_color']))
    <style>
        :root { --accent: {{ $site['primary_color'] }}; }
        a { color: {{ $site['primary_color'] }}; }
        .btn, .filters a.active { background: {{ $site['primary_color'] }}; border-color: {{ $site['primary_color'] }}; color:#fff; }
        .hero, .banner-slide { background-color: {{ $site['primary_color'] }}; }
    </style>
@endif
