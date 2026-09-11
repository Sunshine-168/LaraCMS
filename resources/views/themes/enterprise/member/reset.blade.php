@extends('themes.enterprise.partials.layout')
@section('content')
<section class="wrap" style="max-width:480px;margin:40px auto">
    <h1>设置新密码</h1>
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('member.reset.submit') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <p><label>新密码<br><input type="password" name="password" required minlength="6" style="width:100%;padding:8px"></label></p>
        <p><label>确认密码<br><input type="password" name="password_confirmation" required style="width:100%;padding:8px"></label></p>
        <p><button type="submit">保存</button></p>
    </form>
</section>
@endsection
