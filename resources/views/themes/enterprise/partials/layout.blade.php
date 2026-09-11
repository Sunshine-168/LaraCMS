<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        :root { --ink:#0f2744; --accent:#1b4f72; --line:#dbe3ee; --bg:#f4f7fb; }
        *{box-sizing:border-box} body{margin:0;font-family:"Noto Sans SC","Segoe UI",sans-serif;color:var(--ink);background:linear-gradient(180deg,#e8eef7 0%,var(--bg) 280px)}
        a{color:var(--accent);text-decoration:none} .wrap{max-width:1080px;margin:0 auto;padding:0 20px}
        header{padding:22px 0;border-bottom:1px solid var(--line);background:rgba(255,255,255,.75);backdrop-filter:blur(6px)}
        .brand{font-size:26px;font-weight:700} nav{display:flex;flex-wrap:wrap;gap:14px;margin-top:12px;align-items:center}
        .lang{margin-left:auto;font-size:13px} .hero{min-height:320px;display:grid;place-items:center;background:center/cover no-repeat #1b4f72;color:#fff;margin:24px 0;border-radius:12px}
        .hero h1{font-size:40px;margin:0 0 10px} .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
        .card-soft{background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px}
        h1,h2{line-height:1.25} .muted{color:#64748b;font-size:14px} footer{margin-top:48px;padding:24px 0;border-top:1px solid var(--line);color:#64748b;font-size:13px}
        .btn{display:inline-block;padding:8px 14px;background:var(--accent);color:#fff;border-radius:6px} .content{line-height:1.75;font-size:16px}
        .banner-slide{min-height:280px;border-radius:12px;background:#1b4f72 center/cover;color:#fff;display:flex;align-items:flex-end;padding:24px;margin-bottom:16px}
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
                <form method="post" action="{{ route('member.logout') }}" style="display:inline">@csrf<button type="submit" style="background:none;border:0;color:inherit;cursor:pointer">退出</button></form>
            @else
                <a href="{{ route('member.login') }}">会员</a>
            @endauth
            <span class="lang"><a href="/">中文</a> · <a href="/en">EN</a></span>
        </nav>
    </div>
</header>
<main class="wrap">
    @yield('content')
</main>
<footer><div class="wrap">&copy; {{ date('Y') }} {{ $site['title'] ?? config('app.name') }}</div></footer>
{!! $site['analytics_code'] ?? '' !!}
</body>
</html>
