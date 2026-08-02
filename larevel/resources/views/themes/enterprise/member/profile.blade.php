@extends('themes.enterprise.partials.layout')
@section('content')
<section class="wrap" style="max-width:480px;margin:40px auto">
    <h1>会员中心</h1>
    <p>你好，{{ $member->name }}（{{ $member->email }}）
        @if($member->group) · 分组：{{ $member->group->name }}@endif
    </p>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif

    <h2>资料</h2>
    <form method="post" action="{{ route('member.profile.update') }}">
        @csrf
        <p><label>昵称<br><input type="text" name="name" value="{{ old('name', $member->name) }}" required style="width:100%;padding:8px"></label></p>
        <p><button type="submit">保存资料</button></p>
    </form>

    <h2>修改密码</h2>
    <form method="post" action="{{ route('member.password') }}">
        @csrf
        <p><label>当前密码<br><input type="password" name="current_password" required style="width:100%;padding:8px"></label></p>
        <p><label>新密码<br><input type="password" name="password" required minlength="6" style="width:100%;padding:8px"></label></p>
        <p><label>确认新密码<br><input type="password" name="password_confirmation" required style="width:100%;padding:8px"></label></p>
        <p><button type="submit">保存密码</button></p>
    </form>
    <form method="post" action="{{ route('member.logout') }}" style="margin-top:20px">@csrf
        <button type="submit">退出登录</button>
    </form>
</section>
@endsection
