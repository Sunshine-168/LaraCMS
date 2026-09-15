<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body{margin:0;font-family:"Barlow","Noto Sans SC",sans-serif;background:#04110d;color:#ecfdf5}
        a{color:#6ee7b7;text-decoration:none}.wrap{max-width:980px;margin:0 auto;padding:0 16px}
        header{padding:18px 0;border-bottom:1px solid #064e3b}.brand{font-size:26px;font-weight:800;color:#fff}
        nav{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}
        .card{background:#06281f;border:1px solid #065f46;border-radius:12px;padding:14px;margin:10px 0}
        .live{display:inline-block;background:#dc2626;color:#fff;font-size:11px;padding:2px 8px;border-radius:999px}
        .muted{color:#6ee7b7;opacity:.8;font-size:13px}.btn{display:inline-block;background:#10b981;color:#042f2e;padding:8px 12px;border-radius:6px;font-weight:700}
        .card-soft{background:#06281f;border:1px solid #065f46;border-radius:12px;padding:14px;margin:10px 0}
        input,textarea,select{font:inherit;padding:8px;border:1px solid #065f46;border-radius:6px;background:#04110d;color:#ecfdf5}
        .filters a{margin-right:8px;padding:4px 10px;border:1px solid #065f46;border-radius:999px;font-size:13px}
        .filters a.active{background:#059669;border-color:#059669;color:#fff}
    </style>
    @include('cms.partials.front-appearance')
</head>
<body>
<header><div class="wrap">
    @include('cms.partials.front-brand', ['fallback' => 'Sports'])
    <nav>
        @include('cms.partials.header-nav')
        @include('cms.partials.front-toolbar')
        @include('cms.partials.lang-switch')
    </nav>
</div></header>
<main class="wrap" style="padding:20px 0">@yield('content')</main>
<p class="wrap muted" style="padding-bottom:24px">仅作体育资讯/公开源目录演示，请遵守当地法律法规与版权要求。</p>
@include('cms.partials.front-footer')
</body>
</html>
