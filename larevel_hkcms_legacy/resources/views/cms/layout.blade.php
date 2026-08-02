<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? ($site['title'] ?? 'CMS') }}</title>
    <meta name="keywords" content="{{ $seoKeywords ?? '' }}">
    <meta name="description" content="{{ $seoDescription ?? '' }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #222; background: #f6f7fb; }
        a { color: #2563eb; text-decoration: none; }
        .container { width: min(1100px, calc(100% - 32px)); margin: 0 auto; }
        .header { background: #111827; color: #fff; padding: 24px 0; margin-bottom: 24px; }
        .header a { color: #fff; margin-right: 16px; }
        .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 6px 24px rgba(15, 23, 42, 0.06); }
        .grid { display: grid; gap: 16px; }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .list-item { padding: 16px 0; border-bottom: 1px solid #e5e7eb; }
        .list-item:last-child { border-bottom: 0; }
        .meta { color: #6b7280; font-size: 14px; }
        .breadcrumb { margin-bottom: 16px; color: #6b7280; }
        .content { line-height: 1.8; }
        .pagination { margin-top: 20px; }
        .pagination svg { width: 16px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 style="margin: 0 0 12px;">{{ $site['title'] ?? 'CMS' }}</h1>
            <nav>
                <a href="{{ route('cms.home') }}">首页</a>
                @foreach(($navCategories ?? $categories ?? []) as $navCategory)
                    <a href="{{ $navCategory->url() }}">{{ $navCategory->title }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="container" style="padding-bottom: 40px;">
        @yield('content')
    </main>
</body>
</html>
