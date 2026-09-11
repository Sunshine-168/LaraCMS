<a href="{{ cms_front_url('search') }}">{{ cms_t('搜索', 'Search') }}</a>
@auth('member')
    @if(!empty($frontToolbarLogout))
        <form method="post" action="{{ cms_route('member.logout') }}" style="display:inline">@csrf<button type="submit" style="background:none;border:0;color:inherit;cursor:pointer;font:inherit">{{ cms_t('退出', 'Log out') }}</button></form>
    @else
        <a href="{{ cms_front_url('member/profile') }}">{{ cms_t('会员', 'Member') }}</a>
    @endif
@else
    <a href="{{ cms_front_url('member/login') }}">{{ cms_t('会员', 'Member') }}</a>
@endauth
