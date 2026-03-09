<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>后台登录</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #111827, #1d4ed8); min-height: 100vh; display: grid; place-items: center; }
        .card { width: min(420px, calc(100% - 32px)); background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25); }
        .title { font-size: 24px; margin: 0 0 8px; color: #111827; }
        .meta { color: #6b7280; margin-bottom: 20px; }
        label { display: block; margin: 0 0 6px; font-size: 14px; color: #374151; }
        input { width: 100%; box-sizing: border-box; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 10px; margin-bottom: 14px; }
        .btn { width: 100%; border: 0; border-radius: 10px; padding: 12px; background: #2563eb; color: #fff; cursor: pointer; }
        .error { background: #fef2f2; color: #b91c1c; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: 14px; }
    </style>
</head>
<body>
    <form class="card" method="post" action="{{ route('admin.login') }}">
        @csrf
        <h1 class="title">后台登录</h1>
        <div class="meta">当前是 Laravel 兼容迁移版后台入口。</div>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <input type="hidden" name="url" value="{{ old('url', $redirectUrl) }}">

        <label for="username">用户名</label>
        <input id="username" name="username" value="{{ old('username') }}" required>

        <label for="password">密码</label>
        <input id="password" name="password" type="password" required>

        <button class="btn" type="submit">登录</button>
    </form>
</body>
</html>
