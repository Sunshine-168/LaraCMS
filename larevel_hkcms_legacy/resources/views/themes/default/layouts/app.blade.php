<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @cmsSeo
    <style>
        body { font-family: Georgia, "Times New Roman", "PingFang SC", "Microsoft YaHei", serif; margin: 0; color: #1f2937; background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%); min-height: 100vh; }
        a { color: #1d4ed8; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .container { width: min(1100px, calc(100% - 32px)); margin: 0 auto; }
        .header { background: #0f172a; color: #fff; padding: 24px 0; margin-bottom: 24px; }
        .header a { color: #fff; margin-right: 16px; }
        .header h1 { margin: 0 0 12px; font-size: 28px; letter-spacing: 0.02em; }
        .card { background: rgba(255,255,255,0.92); border-radius: 12px; padding: 20px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); }
        .grid { display: grid; gap: 16px; }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .list-item { padding: 16px 0; border-bottom: 1px solid #e5e7eb; }
        .list-item:last-child { border-bottom: 0; }
        .meta { color: #6b7280; font-size: 14px; }
        .breadcrumb { margin-bottom: 16px; color: #6b7280; }
        .content { line-height: 1.8; }
        .pagination { margin-top: 20px; }
        .pagination nav { display: flex; flex-wrap: wrap; gap: 8px; }
        .pagination a, .pagination span { display: inline-block; padding: 6px 10px; border-radius: 6px; background: #fff; border: 1px solid #d1d5db; color: #374151; }
        .pagination span[aria-current="page"] span,
        .pagination .current { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .footer { margin: 40px 0 24px; color: #6b7280; font-size: 14px; text-align: center; }
        .prev-next { display: flex; justify-content: space-between; gap: 16px; margin-top: 24px; }
    </style>
</head>
<body>
    @include('cms_theme::partials.header')

    <main class="container" style="padding-bottom: 40px;">
        @yield('content')
    </main>

    @include('cms_theme::partials.footer')
</body>
</html>
