<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body{margin:0;font-family:"Nunito","Noto Sans SC",sans-serif;background:#121212;color:#f5f5f5}
        a{color:#fbbf24;text-decoration:none}.wrap{max-width:1000px;margin:0 auto;padding:0 16px}
        header{padding:18px 0;border-bottom:1px solid #262626}.brand{font-size:24px;font-weight:800;color:#fff}
        nav{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-top:16px}
        .card{background:#1a1a1a;border:1px solid #2a2a2a;border-radius:10px;overflow:hidden}
        .card img{width:100%;aspect-ratio:3/4;object-fit:cover;display:block}
        .card .body{padding:10px}.muted{color:#a3a3a3;font-size:12px}
        .pages img{width:100%;max-width:860px;display:block;margin:0 auto 8px}
        .btn{display:inline-block;background:#f59e0b;color:#111;padding:8px 12px;border-radius:6px;font-weight:700}
        .card-soft{background:#1a1a1a;border:1px solid #2a2a2a;border-radius:10px;padding:14px;margin:10px 0}
        input,textarea,select{font:inherit;padding:8px;border:1px solid #2a2a2a;border-radius:6px;background:#121212;color:#f5f5f5}
    </style>
    @include('cms.partials.front-appearance')
</head>
<body>
<header><div class="wrap">
    @include('cms.partials.front-brand', ['fallback' => 'Manga'])
    <nav>
        @include('cms.partials.header-nav')
        @include('cms.partials.front-toolbar')
        @include('cms.partials.lang-switch')
    </nav>
</div></header>
<main class="wrap" style="padding:20px 0">@yield('content')</main>
@include('cms.partials.front-footer')
</body>
</html>
