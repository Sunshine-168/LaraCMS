@extends('themes.sports.partials.layout')
@section('content')
    <h1>会员登录</h1>
    <form method="post" action="{{ route('member.login.submit') }}" class="card">@csrf
        <input type="email" name="email" value="{{ old('email') }}" required placeholder="邮箱">
        <input type="password" name="password" required placeholder="密码">
        <p><button class="btn" type="submit">登录</button></p>
    </form>
@endsection
