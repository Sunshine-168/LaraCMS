<!DOCTYPE html>
<html lang="{{ \App\Support\AdminUi::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ admin_t('auth.reset_title') }} - CMS</title>
    <link rel="stylesheet" href="https://cdn.staticfile.net/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
<div class="login-box">
    <h1>{{ admin_t('auth.reset_title') }}</h1>
    <p class="sub">{{ $email }}</p>
    <form method="post" action="{{ route('admin.password.reset.submit') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <label>{{ admin_t('auth.new_password') }}</label>
        <input type="password" name="password" required minlength="8" placeholder="{{ admin_t('auth.min8') }}">
        <label>{{ admin_t('auth.again') }}</label>
        <input type="password" name="password_confirmation" required minlength="8">
        @error('email')<div class="login-error">{{ $message }}</div>@enderror
        @error('password')<div class="login-error">{{ $message }}</div>@enderror
        <button type="submit">{{ admin_t('auth.save_login') }}</button>
    </form>
    <p class="login-links"><a href="{{ route('admin.login') }}">{{ admin_t('auth.back_login') }}</a></p>
    @include('admin.auth._ui-switch')
</div>
</body>
</html>
