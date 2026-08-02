<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>后台登录 - CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
<div class="login-box">
    <h1><i class="fas fa-lock" style="color:#007bff;margin-right:6px"></i>CMS 登录</h1>
    <p class="sub">综合内容管理系统 · 参考 HkCms 后台体验</p>
    <form method="post" action="{{ route('admin.login.submit') }}">
        @csrf
        <label>邮箱</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
        <label>密码</label>
        <input type="password" name="password" required placeholder="请输入密码">
        <label class="remember"><input type="checkbox" name="remember" value="1"> 记住我</label>
        @error('email')<div class="login-error">{{ $message }}</div>@enderror
        <button type="submit">登 录</button>
    </form>
</div>
</body>
</html>
