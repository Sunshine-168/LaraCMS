@extends('themes.source.partials.layout')
@section('content')
    <h1>会员登录</h1>
    <form method="post" action="{{ route('member.login.submit') }}" class="card">
        @csrf
        <label>邮箱</label><input type="email" name="email" value="{{ old('email') }}" required>
        <label>密码</label><input type="password" name="password" required>
        <p><button class="btn" type="submit">登录</button></p>
    </form>
@endsection
