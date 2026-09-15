@extends('admin.layout')
@section('title', admin_t('nav.groups'))
@section('icon', 'user-tag')
@php
    $composeFocus = session('group_quick_added') || $groups->isEmpty();
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.members.index') }}">会员</a>
@endsection
@section('content')
    <div class="entry-index member-group-index">
        <div class="member-group-compose">
            <form method="post" action="{{ route('admin.member-groups.store') }}">
                @csrf
                <label class="member-group-compose-label" for="group-quick-name">新增分组</label>
                <div class="member-group-compose-row">
                    <input id="group-quick-name" type="text" name="name" value="" placeholder="显示名，如 普通会员" aria-label="分组名称" required @if($composeFocus) autofocus @endif>
                    <input class="group-key" type="text" name="slug" value="" placeholder="标识码，如 default" aria-label="标识码">
                    <button class="btn" type="submit">添加</button>
                </div>
                <p class="muted field-hint">决定会员在前台能投稿、下载还是只看看。添加后点名称去勾权限。需要一次勾好时，<a href="{{ route('admin.member-groups.create') }}">打开完整表单</a>。</p>
            </form>
        </div>

        @if($groups->isEmpty())
            <div class="list-empty">
                <p>还没有分组。</p>
                <p class="muted">在上方填写名称即可添加。会员按组决定能做什么。</p>
            </div>
        @else
            <p class="muted recycle-lead">点名称去改权限。停用后这组前台能力关掉，人还在名单里。删掉后会员变成未分组。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>分组</th>
                    <th style="width:120px">会员</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups as $i => $g)
                    <tr @class(['is-off' => ! $g->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.member-groups.edit', $g) }}">{{ $g->name ?: '未命名' }}</a>
                                @unless($g->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">{{ $g->slug }}</div>
                        </td>
                        <td>
                            @if(($g->members_count ?? 0) > 0)
                                <a href="{{ route('admin.members.index', ['group' => $g->id]) }}">{{ $g->members_count }} 人</a>
                            @else
                                <span class="muted">还没人</span>
                            @endif
                        </td>
                        <td class="actions">
                            @if($i > 0)
                                <form action="{{ route('admin.member-groups.move', $g) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="dir" value="up">
                                    <button class="btn btn-sm btn-muted" type="submit">上移</button>
                                </form>
                            @endif
                            @if($i < $groups->count() - 1)
                                <form action="{{ route('admin.member-groups.move', $g) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="dir" value="down">
                                    <button class="btn btn-sm btn-muted" type="submit">下移</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.member-groups.toggle', $g) }}" method="post">
                                @csrf
                                <button class="btn btn-sm btn-muted" type="submit">{{ $g->is_active ? '停用' : '启用' }}</button>
                            </form>
                            <form action="{{ route('admin.member-groups.destroy', $g) }}" method="post" onsubmit="return confirm(@json(($g->members_count ?? 0) > 0 ? '「'.$g->name.'」还有 '.$g->members_count.' 人。删掉后他们变成未分组，确定？' : '确定删除「'.($g->name ?: $g->slug).'」？'))">
                                @csrf @method('DELETE')
                                <button class="btn-quiet" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $groups->count() }} 组</p>
        @endif
    </div>
@endsection
