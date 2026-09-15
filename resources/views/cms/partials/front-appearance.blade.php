@if(!empty($site['favicon_url']))
    <link rel="icon" href="{{ $site['favicon_url'] }}">
@endif
<style>
    html{-webkit-text-size-adjust:100%}
    img,video{max-width:100%;height:auto}
    .brand{display:inline-flex;align-items:center;gap:10px}
    .brand img{max-height:36px;width:auto;display:block}
    footer{margin-top:36px;padding:24px 0;font-size:13px}
    .lang a[aria-current="page"]{font-weight:700;text-decoration:none;pointer-events:none;opacity:.88}
    .sitemap-page-block{margin:20px 0}
    .sitemap-page-block h2{margin:0 0 8px;font-size:18px}
    .sitemap-page-block ul{margin:0;padding-left:1.2em;line-height:1.9}
    .sitemap-page-block a{word-break:break-all}
    @media (max-width:768px){
        .wrap{padding-left:14px;padding-right:14px}
        header nav{flex-wrap:wrap;gap:10px}
        .lang{margin-left:0}
        .hero{min-height:200px}
        .hero h1{font-size:clamp(22px,6vw,32px)}
        h1{font-size:clamp(22px,6vw,32px)}
        .grid{grid-template-columns:1fr}
        .banner-slide{min-height:180px}
    }
</style>
@if(!empty($site['primary_color']))
    <style>
        :root { --accent: {{ $site['primary_color'] }}; }
        a { color: {{ $site['primary_color'] }}; }
        .btn, .filters a.active { background: {{ $site['primary_color'] }}; border-color: {{ $site['primary_color'] }}; color:#fff; }
        .hero, .banner-slide { background-color: {{ $site['primary_color'] }}; }
    </style>
@endif
