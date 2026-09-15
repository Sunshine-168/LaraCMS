<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '提示')</title>
    <style>
        body{margin:0;font-family:"Noto Sans SC","Segoe UI",sans-serif;background:#f4f7fb;color:#0f2744;display:grid;place-items:center;min-height:100vh}
        .box{max-width:480px;padding:32px;background:#fff;border:1px solid #dbe3ee;border-radius:12px;text-align:center}
        h1{font-size:22px;margin:0 0 12px} p{color:#64748b;line-height:1.6}
        a{color:#1b4f72}
    </style>
</head>
<body>
<div class="box">
    @yield('content')
</div>
</body>
</html>
