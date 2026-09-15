<a href="{{ cms_front_url('search') }}">{{ cms_ui('search') }}</a>
@auth('member')
    @if(!empty($frontToolbarLogout))
        <form method="post" action="{{ cms_route('member.logout') }}" style="display:inline">@csrf<button type="submit" style="background:none;border:0;color:inherit;cursor:pointer;font:inherit">{{ cms_ui('logout') }}</button></form>
    @else
        <a href="{{ cms_front_url('member/profile') }}">{{ cms_ui('member') }}</a>
    @endif
@else
    <a href="{{ cms_front_url('member/login') }}">{{ cms_ui('login') }}</a>
@endauth
