<section class="wrap" style="max-width:640px;margin:40px auto">
    <h1>{{ cms_ui('member_center') }}</h1>
    <p>{{ cms_ui('hello', ['name' => $member->name]) }}（{{ $member->email }}）
        @if($member->group) · {{ cms_ui('group') }}：{{ $member->group->name }}@endif
    </p>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if(session('error'))<p style="color:#c00">{{ session('error') }}</p>@endif
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif

    <h2>{{ cms_ui('profile') }}</h2>
    <form method="post" action="{{ cms_route('member.profile.update') }}">
        @csrf
        <p>
            <img id="memberAvatarPreview" src="{{ cms_media($member->avatar) }}" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;{{ $member->avatar ? '' : 'display:none' }}">
        </p>
        <p><label>{{ cms_ui('avatar') }}<br><input type="text" id="memberAvatar" name="avatar" value="{{ old('avatar', $member->avatar) }}" style="width:100%;padding:8px"></label></p>
        @if($member->canMember('upload'))
            <p class="muted">分片上传头像（大文件自动切块）</p>
            @include('cms.partials.member-chunk-upload', ['purpose' => 'avatar', 'targetInput' => 'memberAvatar', 'previewId' => 'memberAvatarPreview'])
        @endif
        <p><label>{{ cms_ui('nickname') }}<br><input type="text" name="name" value="{{ old('name', $member->name) }}" required style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('phone') }}<br><input type="text" name="phone" value="{{ old('phone', $member->phone) }}" maxlength="32" style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('bio') }}<br><textarea name="bio" rows="3" maxlength="500" style="width:100%;padding:8px">{{ old('bio', $member->bio) }}</textarea></label></p>
        <p><button type="submit">{{ cms_ui('save_profile') }}</button></p>
    </form>

    <h2>{{ cms_ui('change_password') }}</h2>
    <form method="post" action="{{ cms_route('member.password') }}">
        @csrf
        <p><label>{{ cms_ui('current_password') }}<br><input type="password" name="current_password" required style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('new_password') }}<br><input type="password" name="password" required minlength="10" style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('password_confirm') }}<br><input type="password" name="password_confirmation" required style="width:100%;padding:8px"></label></p>
        <p><button type="submit">{{ cms_ui('save_password') }}</button></p>
    </form>
    @include('cms.partials.member-hub')
    <form method="post" action="{{ cms_route('member.logout') }}" style="margin-top:20px">@csrf
        <button type="submit">{{ cms_ui('logout') }}</button>
    </form>
</section>
