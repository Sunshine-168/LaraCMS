@extends('admin.layout')
@section('title', $member->exists ? '编辑会员' : '新建会员')
@section('icon', 'users')
@php
    $posts = (int) ($member->contributed_entries_count ?? 0);
    $comments = (int) ($member->comments_count ?? 0);
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.members.index') }}">{{ admin_back('members') }}</a>
@endsection
@section('content')
    @if($member->exists && ($posts > 0 || $comments > 0))
        <p class="muted recycle-lead">
            @if($posts > 0)有 {{ $posts }} 篇投稿@endif
            @if($posts > 0 && $comments > 0)，@endif
            @if($comments > 0)留过 {{ $comments }} 条评论@endif
            。改资料或停用不会删掉这些内容。
        </p>
    @endif
    <form method="post" class="member-form" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}">
        @csrf
        @if($member->exists) @method('PUT') @endif

        <label for="member-name">名称</label>
        <input id="member-name" type="text" name="name" value="{{ old('name', $member->name) }}" placeholder="如 张三" required autofocus>
        <p class="muted field-hint">前台个人中心和评论里看到的名字。</p>

        <label for="member-email">登录邮箱</label>
        <input id="member-email" type="email" name="email" value="{{ old('email', $member->email) }}" required>
        <p class="muted field-hint">用来登录网站，不是后台账号。</p>

        <label for="member-group">分组</label>
        <select id="member-group" name="group_id">
            <option value="">未分组</option>
            @foreach($groups ?? [] as $g)
                <option value="{{ $g->id }}" @selected(old('group_id', $member->group_id) == $g->id)>
                    {{ $g->name }}{{ $g->is_active ? '' : '（已停用）' }}
                </option>
            @endforeach
        </select>
        <p class="muted field-hint">决定能不能投稿、下载、留言。分组权限在 <a href="{{ route('admin.member-groups.index') }}">分组</a> 里改。</p>

        <label for="member-password">密码{{ $member->exists ? '（留空不改）' : '' }}</label>
        <input id="member-password" type="password" name="password" autocomplete="new-password" {{ $member->exists ? '' : 'required' }} minlength="8">
        <p class="muted field-hint">{{ $member->exists ? '只有要帮他重设密码时才填。' : '至少 8 位，交给会员自己登录。' }}</p>

        <label for="member-phone">手机</label>
        <input id="member-phone" type="text" name="phone" value="{{ old('phone', $member->phone) }}" maxlength="32" placeholder="可空">

        <label for="member-bio">简介</label>
        <textarea id="member-bio" name="bio" rows="3" maxlength="500" placeholder="可空">{{ old('bio', $member->bio) }}</textarea>

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $member->is_active ?? true))>
            启用，可以登录前台
        </label>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $member->exists ? '保存' : '添加会员' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.members.index') }}">取消</a>
        </div>
    </form>
@endsection
