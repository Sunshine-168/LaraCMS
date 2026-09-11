@extends('themes.enterprise.partials.layout')
@section('content')
<section class="wrap" style="max-width:480px;margin:40px auto">
    <h1>找回密码</h1>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if(session('reset_url'))
        <p class="muted">本地调试链接：</p>
        <p><a href="{{ session('reset_url') }}">{{ session('reset_url') }}</a></p>
    @endif
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ cms_route('member.forgot.submit') }}">
        @csrf
        <p><label>注册邮箱<br><input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:8px"></label></p>
        <p><button type="submit">生成重置链接</button>
            <a href="{{ cms_front_url('member/login') }}">返回登录</a></p>
    </form>
</section>
@endsection
