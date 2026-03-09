<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? '后台管理' }}</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
        a { color: inherit; text-decoration: none; }
        .shell { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        .sidebar { background: #111827; color: #e5e7eb; padding: 20px 16px; }
        .sidebar h1 { font-size: 18px; margin: 0 0 20px; }
        .sidebar .group { margin-bottom: 14px; }
        .sidebar .group-title { font-size: 13px; color: #9ca3af; margin-bottom: 8px; }
        .sidebar .link { display: block; padding: 8px 10px; border-radius: 8px; color: #f9fafb; }
        .sidebar .link:hover, .sidebar .link.active { background: #1f2937; }
        .sidebar .child { padding-left: 12px; }
        .content { padding: 24px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06); }
        .grid { display: grid; gap: 16px; }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .meta { color: #6b7280; font-size: 14px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: 12px; }
        .btn { display: inline-block; padding: 8px 14px; border-radius: 8px; border: 0; background: #2563eb; color: #fff; cursor: pointer; }
        .btn-secondary { background: #e5e7eb; color: #111827; }
        .stats { font-size: 28px; font-weight: 700; margin: 8px 0 0; }
        form.inline { display: inline; }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <h1>Laravel CMS 后台</h1>
        <div class="meta" style="color:#9ca3af;margin-bottom:16px;">
            {{ $adminUser?->nickname ?: $adminUser?->username }}
            @if($adminGroupNames)
                <div style="margin-top:4px;">{{ $adminGroupNames }}</div>
            @endif
        </div>

        @foreach($adminMenuTree as $item)
            <div class="group">
                <div class="group-title">{{ $item['title'] }}</div>
                @if(!empty($item['children']))
                    @foreach($item['children'] as $child)
                        <div class="child">
                            <a class="link {{ url()->current() === $child['url'] ? 'active' : '' }}" href="{{ $child['url'] }}">{{ $child['title'] }}</a>
                        </div>
                    @endforeach
                @elseif(($item['url'] ?? '#') !== '#')
                    <a class="link {{ url()->current() === $item['url'] ? 'active' : '' }}" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                @endif
            </div>
        @endforeach
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <h2 style="margin:0;">{{ $pageTitle ?? '后台管理' }}</h2>
                @isset($pageDescription)
                    <div class="meta" style="margin-top:6px;">{{ $pageDescription }}</div>
                @endisset
            </div>
            <form class="inline" method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">退出登录</button>
            </form>
        </div>

        @if(session('status'))
            <div class="card" style="margin-bottom:16px;background:#ecfdf5;color:#166534;">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="card" style="margin-bottom:16px;background:#fef2f2;color:#b91c1c;">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
