@php
    $commentsEnabled = isset($commentsEnabled) ? (bool) $commentsEnabled : (bool) (int) \App\Models\Cms\Setting::getValue('comments_enabled', '1');
    $commentsGuest = isset($commentsGuest) ? (bool) $commentsGuest : (bool) (int) \App\Models\Cms\Setting::getValue('comments_guest', '1');
    $commentTree = $commentTree ?? collect();
    $member = auth('member')->user();
    $canPost = $commentsEnabled && ($member || $commentsGuest);
@endphp
@include('cms.partials.favorite')
@if($commentsEnabled)
<section class="cms-comments" style="margin-top:36px;padding-top:24px;border-top:1px solid rgba(0,0,0,.08)">
    <h2 style="font-size:1.25rem;margin:0 0 16px">评论 @if($commentTree->count())<span class="muted" style="font-size:.9rem">({{ $commentTree->sum(fn($c) => 1 + $c->children->count()) }})</span>@endif</h2>

    @if(session('status'))
        <p class="muted" style="color:green">{{ session('status') }}</p>
    @endif
    @if(session('error'))
        <p class="muted" style="color:#c0392b">{{ session('error') }}</p>
    @endif

    @forelse($commentTree as $comment)
        <div class="cms-comment" style="margin-bottom:16px">
            <div style="font-weight:600">{{ $comment->author_name }}
                <span class="muted" style="font-weight:400;font-size:.85rem"> · {{ $comment->created_at?->format('Y-m-d H:i') }}</span>
            </div>
            <div style="margin-top:6px;white-space:pre-wrap">{{ $comment->content }}</div>
            @foreach($comment->children as $child)
                <div class="cms-comment-reply" style="margin:12px 0 0 20px;padding-left:12px;border-left:2px solid rgba(0,0,0,.08)">
                    <div style="font-weight:600">{{ $child->author_name }}
                        <span class="muted" style="font-weight:400;font-size:.85rem"> · {{ $child->created_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    <div style="margin-top:6px;white-space:pre-wrap">{{ $child->content }}</div>
                </div>
            @endforeach
        </div>
    @empty
        <p class="muted">暂无评论，来抢沙发吧。</p>
    @endforelse

    @if($canPost)
        <form method="post" action="{{ url(\App\Support\UrlMode::localePrefix().'/comment/'.$entry->id) }}" style="margin-top:24px;max-width:560px">
            @csrf
            @unless($member)
                <label>昵称</label>
                <input type="text" name="author_name" value="{{ old('author_name') }}" required maxlength="80">
                <label>邮箱（可选）</label>
                <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="120">
            @else
                <p class="muted">以会员身份评论：{{ $member->name }}</p>
            @endunless
            <label>评论内容</label>
            <textarea name="content" rows="4" required maxlength="2000">{{ old('content') }}</textarea>
            @if(config('cms.form_captcha', true))
                @php $cap = \App\Support\Captcha::generate(); @endphp
                <label>验证码：{{ $cap['question'] }}</label>
                <input type="text" name="captcha" required maxlength="10" autocomplete="off" style="max-width:120px">
            @endif
            <p style="margin-top:12px">
                <button type="submit">提交评论</button>
            </p>
        </form>
    @elseif(!$member)
        <p class="muted">请 <a href="{{ cms_front_url('member/login') }}">登录</a> 后发表评论。</p>
    @endif
</section>
@endif
