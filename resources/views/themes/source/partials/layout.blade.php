<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body{margin:0;font-family:"JetBrains Mono","Noto Sans SC",sans-serif;background:#111;color:#f5f5f5}
        a{color:#a3e635;text-decoration:none}.wrap{max-width:980px;margin:0 auto;padding:0 16px}
        header{padding:20px 0;border-bottom:1px solid #222}.brand{font-size:22px;font-weight:700;color:#fff}
        nav{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}.card{border:1px solid #333;border-radius:8px;padding:14px;margin:12px 0;background:#171717}
        .btn{display:inline-block;background:#65a30d;color:#052e16;padding:8px 12px;border-radius:6px;font-weight:700}
        .muted{color:#a3a3a3;font-size:13px}.tag{display:inline-block;border:1px solid #404040;padding:2px 8px;border-radius:999px;margin-right:6px;font-size:12px}
        .card-soft{border:1px solid #333;border-radius:8px;padding:14px;margin:12px 0;background:#171717}
        input,textarea,select{font:inherit;padding:8px;border:1px solid #333;border-radius:6px;background:#111;color:#f5f5f5}
    </style>
</head>
<body>
<header><div class="wrap">
    <a class="brand" href="/">{{ $site['title'] ?? '源码站' }}</a>
    <nav>
        @include('cms.partials.header-nav')
        <span class="muted">
            @cmsTaglist(['num'=>8])
                <a class="tag" href="{{ url('/tag/'.$item->t('slug')) }}">{{ $item->t('title') }}</a>
            @endcmsTaglist
        </span>
    </nav>
</div></header>
<main class="wrap" style="padding:20px 0">@yield('content')</main>
</body>
</html>
