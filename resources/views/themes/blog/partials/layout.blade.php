<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body{margin:0;font-family:"Literata","Noto Serif SC",serif;background:#faf8f5;color:#1c1917;line-height:1.7}
        a{color:#9a3412;text-decoration:none}.wrap{max-width:720px;margin:0 auto;padding:0 20px}
        header{padding:40px 0 20px;border-bottom:1px solid #e7e5e4}.brand{font-size:32px;font-weight:700}
        nav{display:flex;gap:14px;flex-wrap:wrap;margin-top:12px;font-family:"DM Sans",sans-serif;font-size:14px}
        article{padding:28px 0;border-bottom:1px solid #e7e5e4}.muted{color:#78716c;font-size:13px;font-family:"DM Sans",sans-serif}
        h1{font-size:34px;line-height:1.25} .content p{margin:0 0 1em}
        .card-soft{background:#fff;border:1px solid #e7e5e4;border-radius:10px;padding:16px;margin:16px 0}
        .btn{display:inline-block;background:#9a3412;color:#fff;padding:8px 14px;border-radius:6px}
        input,textarea,select{font:inherit;padding:8px;border:1px solid #e7e5e4;border-radius:6px}
    </style>
    @include('cms.partials.front-appearance')
</head>
<body>
<header class="wrap">
    @include('cms.partials.front-brand', ['fallback' => 'Blog'])
    <nav>
        @include('cms.partials.header-nav')
        @include('cms.partials.front-toolbar')
        @include('cms.partials.lang-switch')
        <a href="{{ cms_route('rss') }}">RSS</a>
    </nav>
</header>
<main class="wrap">@yield('content')</main>
@include('cms.partials.front-footer')
</body>
</html>
