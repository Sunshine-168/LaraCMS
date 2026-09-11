@extends('themes.enterprise.partials.layout')
@section('content')
    <h1>会员登录</h1>
    @if(session('error'))<p style="color:#b91c1c">{{ session('error') }}</p>@endif
    <form method="post" action="{{ route('member.login.submit') }}" class="card-soft" style="max-width:420px;margin-top:16px">
        @csrf
        <label>邮箱</label>
        <input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:8px;margin:6px 0 12px">
        <label>密码</label>
        <input type="password" name="password" required style="width:100%;padding:8px;margin:6px 0 12px">
        <label><input type="checkbox" name="remember" value="1"> 记住我</label>
        @error('email')<p style="color:#b91c1c">{{ $message }}</p>@enderror
        <p><button class="btn" type="submit">登录</button>
            <a href="{{ route('member.register') }}">注册账号</a>
            <a href="{{ route('member.forgot') }}">忘记密码</a></p>
    </form>
    <p class="muted">演示账号见 README</p>
@endsection
