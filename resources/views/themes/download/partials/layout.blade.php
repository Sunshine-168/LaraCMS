<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body{margin:0;font-family:"IBM Plex Sans","Noto Sans SC",sans-serif;background:#0b1220;color:#e5eefc}
        a{color:#7dd3fc;text-decoration:none}.wrap{max-width:1000px;margin:0 auto;padding:0 16px}
        header{padding:20px 0;border-bottom:1px solid #1e293b}.brand{font-size:24px;font-weight:700;color:#fff}
        nav{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}.card{background:#111827;border:1px solid #1f2937;border-radius:10px;padding:14px;margin:10px 0}
        .btn{display:inline-block;background:#0284c7;color:#fff;padding:8px 12px;border-radius:6px}.muted{color:#94a3b8;font-size:13px}
        .card-soft{background:#111827;border:1px solid #1f2937;border-radius:10px;padding:14px;margin:10px 0}
        input,textarea,select{font:inherit;padding:8px;border:1px solid #1f2937;border-radius:6px;background:#0b1220;color:#e5eefc}
        .filters a{margin-right:8px;padding:4px 8px;border:1px solid #334155;border-radius:999px;font-size:13px}
        .filters a.active{background:#0284c7;border-color:#0284c7;color:#fff}
    </style>
    @include('cms.partials.front-appearance')
</head>
<body>
<header><div class="wrap">
    @include('cms.partials.front-brand', ['fallback' => 'Download'])
    <nav>
        @include('cms.partials.header-nav')
        @include('cms.partials.front-toolbar')
        <a href="/en">EN</a>
    </nav>
</div></header>
<main class="wrap" style="padding:20px 0">@yield('content')</main>
@include('cms.partials.front-footer')
</body>
</html>
