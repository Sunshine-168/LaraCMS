@extends('themes.manga.partials.layout')
@section('content')
    <h1>会员登录</h1>
    <form method="post" action="{{ route('member.login.submit') }}" class="card" style="padding:16px;max-width:360px">@csrf
        <input type="email" name="email" value="{{ old('email') }}" required placeholder="邮箱" style="width:100%;margin:8px 0;padding:8px">
        <input type="password" name="password" required placeholder="密码" style="width:100%;margin:8px 0;padding:8px">
        <button class="btn" type="submit">登录</button>
    </form>
@endsection
