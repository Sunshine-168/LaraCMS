<!DOCTYPE html>
<html lang="{{ \App\Support\AdminUi::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ admin_t('auth.forgot_title') }} - CMS</title>
    <link rel="stylesheet" href="https://cdn.staticfile.net/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
<div class="login-box">
    <h1>{{ admin_t('auth.forgot_title') }}</h1>
    <p class="sub">{{ admin_t('auth.forgot_lead') }}</p>
    <form method="post" action="{{ route('admin.password.forgot.submit') }}">
        @csrf
        <label>{{ admin_t('auth.email') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
        @error('email')<div class="login-error">{{ $message }}</div>@enderror
        @if(session('status'))<div class="login-status">{{ session('status') }}</div>@endif
        @if(session('reset_url'))
            <p class="login-status" style="word-break:break-all">{{ session('reset_url') }}</p>
        @endif
        <button type="submit">{{ admin_t('auth.send') }}</button>
    </form>
    <p class="login-links"><a href="{{ route('admin.login') }}">{{ admin_t('auth.back_login') }}</a></p>
    @include('admin.auth._ui-switch')
</div>
</body>
</html>
