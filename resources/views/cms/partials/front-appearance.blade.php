@if(!empty($site['favicon_url']))
    <link rel="icon" href="{{ $site['favicon_url'] }}">
@endif
<style>
    html{-webkit-text-size-adjust:100%}
    img,video{max-width:100%;height:auto}
    .brand{display:inline-flex;align-items:center;gap:10px}
    .brand img{max-height:36px;width:auto;display:block}
    footer{margin-top:36px;padding:24px 0;font-size:13px}
    .lang a[aria-current="page"], nav a[aria-current="page"]{font-weight:700;text-decoration:none;pointer-events:none;opacity:.88}
    .cms-skip{position:absolute;left:-999px;top:8px;z-index:10001;background:#111;color:#fff;padding:8px 12px;border-radius:6px}
    .cms-skip:focus{left:8px}
    .sitemap-page-block{margin:20px 0}
    .sitemap-page-block h2{margin:0 0 8px;font-size:18px}
    .sitemap-page-block ul{margin:0;padding-left:1.2em;line-height:1.9}
    .sitemap-page-block a{word-break:break-all}
    .home-split, .manga-show, .product-show { display:grid; grid-template-columns:1fr 1.2fr; gap:20px }
    .home-contact { display:grid; grid-template-columns:1fr 1fr; gap:20px }
    .breadcrumb{font-size:13px;margin:8px 0 16px;opacity:.9}
    .cms-form .cms-form-item{margin:0 0 12px}
    .cms-form label, .cms-comments form label{display:block;margin:8px 0 4px}
    .cms-form label.inline, .cms-comments label.inline{display:inline;margin-right:10px}
    .cms-form input[type=text], .cms-form input[type=email], .cms-form input[type=number], .cms-form input[type=tel],
    .cms-form textarea, .cms-form select,
    .cms-comments input[type=text], .cms-comments input[type=email], .cms-comments textarea{
        width:100%;max-width:560px;display:block
    }
    .cms-comments{margin-top:36px;padding-top:24px;border-top:1px solid rgba(127,127,127,.28)}
    .btn,.cms-favorite button,.cms-comments button{cursor:pointer}
    @media (max-width:768px){
        .wrap{padding-left:14px;padding-right:14px}
        header nav{flex-wrap:wrap;gap:10px}
        .lang{margin-left:0}
        .hero{min-height:200px}
        .hero h1{font-size:clamp(22px,6vw,32px)}
        h1{font-size:clamp(22px,6vw,32px)}
        .grid{grid-template-columns:1fr}
        .banner-slide{min-height:180px}
        .home-split, .home-contact, .manga-show, .product-show { grid-template-columns:1fr }
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
