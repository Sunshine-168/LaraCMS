<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body{margin:0;font-family:"DM Sans","Noto Sans SC",sans-serif;background:#faf7f2;color:#1c1917}
        a{color:#0f766e;text-decoration:none}.wrap{max-width:1100px;margin:0 auto;padding:0 16px}
        header{padding:24px 0}.brand{font-size:30px;font-weight:800;letter-spacing:-.02em}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-top:16px}
        .link{background:#fff;border:1px solid #e7e5e4;border-radius:12px;padding:14px;text-align:center}
        .link img{width:40px;height:40px;object-fit:contain}.muted{color:#78716c;font-size:13px}
        .section{margin:28px 0} nav{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}
    </style>
</head>
<body>
<header><div class="wrap">
    <a class="brand" href="/">{{ $site['title'] ?? '导航' }}</a>
    <nav>
        @cmsChannel(['type'=>'top','ismenu'=>1])
            <a href="{{ $item->frontUrl() }}">{{ $item->t('title') }}</a>
        @endcmsChannel
    </nav>
</div></header>
<main class="wrap">@yield('content')</main>
</body>
</html>
