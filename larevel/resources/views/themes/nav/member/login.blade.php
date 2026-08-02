@extends('themes.nav.partials.layout')
@section('content')
    <h1>会员登录</h1>
    <form method="post" action="{{ route('member.login.submit') }}" class="link" style="max-width:400px;text-align:left;padding:20px">
        @csrf
        <label>邮箱</label><br><input type="email" name="email" value="{{ old('email') }}" required style="width:100%;margin:8px 0">
        <label>密码</label><br><input type="password" name="password" required style="width:100%;margin:8px 0">
        <button type="submit">登录</button>
    </form>
@endsection
