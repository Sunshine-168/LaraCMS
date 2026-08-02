@extends('themes.blog.partials.layout')
@section('content')
    <h1>会员登录</h1>
    <form method="post" action="{{ route('member.login.submit') }}">@csrf
        <input type="email" name="email" placeholder="邮箱" value="{{ old('email') }}" required>
        <input type="password" name="password" placeholder="密码" required>
        <button type="submit">登录</button>
    </form>
@endsection
