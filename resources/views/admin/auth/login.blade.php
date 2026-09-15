<!DOCTYPE html>
<html lang="{{ \App\Support\AdminUi::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ admin_t('auth.login_title') }} - CMS</title>
    <link rel="stylesheet" href="https://cdn.staticfile.net/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
<div class="login-box">
    <h1><i class="fas fa-lock" style="color:#007bff;margin-right:6px"></i>{{ admin_t('auth.login_title') }}</h1>
    <p class="sub">{{ admin_t('auth.sub') }}</p>
    <form method="post" action="{{ route('admin.login.submit') }}">
        @csrf
        <label>{{ admin_t('auth.email') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
        <label>{{ admin_t('auth.password') }}</label>
        <input type="password" name="password" required>
        <label class="remember"><input type="checkbox" name="remember" value="1"> {{ admin_t('auth.remember') }}</label>
        @include('cms.partials.auth-captcha')
        @error('email')<div class="login-error">{{ $message }}</div>@enderror
        @if(session('status'))<div class="login-status">{{ session('status') }}</div>@endif
        <button type="submit">{{ admin_t('auth.submit') }}</button>
    </form>
    <p class="login-links"><a href="{{ route('admin.password.forgot') }}">{{ admin_t('auth.forgot') }}</a></p>
    @include('admin.auth._ui-switch')
</div>
</body>
</html>
