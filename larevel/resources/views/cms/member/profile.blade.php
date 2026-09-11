@extends($layout)

@section('content')
<section class="wrap" style="max-width:640px;margin:40px auto">
    <h1>会员中心</h1>
    <p>你好，{{ $member->name }}（{{ $member->email }}）
        @if($member->group) · 分组：{{ $member->group->name }}@endif
    </p>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif

    <h2>资料</h2>
    <form method="post" action="{{ route('member.profile.update') }}">
        @csrf
        <p>
            <img id="memberAvatarPreview" src="{{ $member->avatar }}" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;{{ $member->avatar ? '' : 'display:none' }}">
        </p>
        <p><label>头像 URL<br><input type="text" id="memberAvatar" name="avatar" value="{{ old('avatar', $member->avatar) }}" style="width:100%;padding:8px"></label></p>
        @if($member->canMember('upload'))
            <p class="muted">分片上传头像（大文件自动切块）</p>
            @include('cms.partials.member-chunk-upload', ['purpose' => 'avatar', 'targetInput' => 'memberAvatar', 'previewId' => 'memberAvatarPreview'])
        @endif
        <p><label>昵称<br><input type="text" name="name" value="{{ old('name', $member->name) }}" required style="width:100%;padding:8px"></label></p>
        <p><label>手机<br><input type="text" name="phone" value="{{ old('phone', $member->phone) }}" maxlength="32" style="width:100%;padding:8px"></label></p>
        <p><label>简介<br><textarea name="bio" rows="3" maxlength="500" style="width:100%;padding:8px">{{ old('bio', $member->bio) }}</textarea></label></p>
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
    @include('cms.partials.member-hub')
    <form method="post" action="{{ route('member.logout') }}" style="margin-top:20px">@csrf
        <button type="submit">退出登录</button>
    </form>
</section>
@endsection
