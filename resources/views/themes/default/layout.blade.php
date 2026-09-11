@php
    use App\Support\Locale;
    $locale = Locale::current();
    $prefix = (! Locale::hideDefault() || $locale !== Locale::default())
        ? '/'.Locale::toPublic($locale)
        : '';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'zh_cn' ? 'zh-CN' : $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['title'] ?? config('app.name') }}</title>
    @if(!empty($seo['description']))
        <meta name="description" content="{{ $seo['description'] }}">
    @endif
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <style>
        :root { --ink:#14213d; --accent:#1d3557; --bg:#f8fafc; --line:#e2e8f0; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Source Han Sans SC","Noto Sans SC","Segoe UI",sans-serif; color:var(--ink); background:linear-gradient(180deg,#eef2ff 0%, var(--bg) 240px); }
        a { color:var(--accent); text-decoration:none; }
        .wrap { max-width:960px; margin:0 auto; padding:0 20px; }
        header { padding:28px 0 18px; border-bottom:1px solid var(--line); margin-bottom:28px; }
        .brand { font-size:28px; font-weight:700; letter-spacing:.02em; color:var(--ink); }
        nav { display:flex; flex-wrap:wrap; gap:14px; margin-top:14px; align-items:center; }
        nav a { font-size:15px; }
        .lang { margin-left:auto; font-size:13px; }
        main { min-height:50vh; padding-bottom:48px; }
        h1 { font-size:34px; margin:0 0 16px; line-height:1.25; }
        .content { line-height:1.75; font-size:17px; }
        .content p { margin:0 0 1em; }
        .post-list { list-style:none; padding:0; margin:0; }
        .post-list li { padding:16px 0; border-bottom:1px solid var(--line); }
        .meta { color:#64748b; font-size:13px; margin-bottom:8px; }
        .card-soft { background:#fff; border:1px solid var(--line); border-radius:10px; padding:16px; margin:12px 0; }
        .btn { display:inline-block; background:var(--accent); color:#fff; padding:8px 14px; border-radius:6px; }
        .muted { color:#64748b; font-size:13px; }
        footer { border-top:1px solid var(--line); padding:24px 0; color:#64748b; font-size:13px; }
    </style>
</head>
<body>
<header>
    <div class="wrap">
        <a class="brand" href="{{ $prefix ?: '/' }}">{{ $seo['site'] ?? config('app.name') }}</a>
        <nav>
            @include('cms.partials.header-nav')
            <a href="{{ $prefix }}/search">{{ $locale === 'en' ? 'Search' : '搜索' }}</a>
            <span class="lang">
                <a href="/">中文</a> · <a href="/en">EN</a>
            </span>
        </nav>
    </div>
</header>
<main class="wrap">
    @yield('content')
</main>
<footer>
    <div class="wrap">&copy; {{ date('Y') }} {{ $seo['site'] ?? config('app.name') }}</div>
</footer>
</body>
</html>
