<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @cmsSeo
    <style>
        :root { --ink:#111827; --accent:#0ea5e9; --line:#e5e7eb; --bg:#f8fafc; }
        *{box-sizing:border-box} body{margin:0;font-family:"Noto Sans SC","Segoe UI",sans-serif;color:var(--ink);background:var(--bg);font-size:16px}
        a{color:var(--accent);text-decoration:none} .wrap{max-width:640px;margin:0 auto;padding:0 14px}
        header{padding:14px 0;border-bottom:1px solid var(--line);background:#fff;position:sticky;top:0;z-index:10}
        .brand{font-size:20px;font-weight:700} nav{display:flex;flex-wrap:nowrap;gap:12px;margin-top:10px;overflow-x:auto;-webkit-overflow-scrolling:touch}
        nav a{white-space:nowrap;font-size:14px} .hero{min-height:180px;display:grid;place-items:center;background:#0ea5e9;color:#fff;margin:14px 0;border-radius:10px;padding:16px}
        .hero h1{font-size:24px;margin:0 0 8px} .grid{display:grid;grid-template-columns:1fr;gap:12px}
        .card-soft{background:#fff;border:1px solid var(--line);border-radius:10px;padding:14px}
        .muted{color:#64748b;font-size:13px} footer{margin-top:36px;padding:18px 0;border-top:1px solid var(--line);color:#64748b;font-size:12px}
        .btn{display:inline-block;padding:10px 14px;background:var(--accent);color:#fff;border-radius:8px} .content{line-height:1.7}
        .banner-slide{min-height:160px;border-radius:10px;background:#0369a1 center/cover;color:#fff;display:flex;align-items:flex-end;padding:16px;margin-bottom:12px}
    </style>
</head>
<body>
<header>
    <div class="wrap">
        <a class="brand" href="/">{{ $site['title'] ?? config('app.name') }}</a>
        <nav>
            @include('cms.partials.header-nav')
            <a href="{{ route('search') }}">搜索</a>
            @auth('member')
                <a href="{{ route('member.profile') }}">我的</a>
            @else
                <a href="{{ route('member.login') }}">会员</a>
            @endauth
        </nav>
    </div>
</header>
<main class="wrap">
    @yield('content')
</main>
<footer><div class="wrap">移动版 · &copy; {{ date('Y') }} {{ $site['title'] ?? config('app.name') }}</div></footer>
{!! $site['analytics_code'] ?? '' !!}
</body>
</html>
