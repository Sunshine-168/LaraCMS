<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安装完毕</title>
    <link rel="stylesheet" href="{{ asset('css/install.css') }}?v=1">
</head>
<body>
<div class="wrap">
    <div class="brand"><span class="logo">C</span> CMS 安装向导</div>
    <div class="card" style="display:block">
        <div class="done">
            <div class="mark">✓</div>
            <h1>安装完毕</h1>
            <p class="muted"><strong>{{ $siteName }}</strong> 可以使用了。请用 <strong>{{ $email }}</strong> 和刚才设置的密码登录后台。</p>
            @if($demo)
                <p class="hint">示例栏目和文章已经导入。上线前请改掉演示账号，不要把默认密码留在公网。</p>
            @endif
            <div class="btns">
                <a class="btn" href="{{ url('/admin/login') }}">进入后台</a>
                <a class="btn-muted" href="{{ url('/') }}">打开网站</a>
                <a class="btn-muted" href="{{ url('/install?way=deploy') }}">上线部署说明</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
