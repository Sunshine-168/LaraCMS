@extends('admin.layout')
@section('title', '留言收件箱')
@section('icon', 'inbox')
@php
    $q = $q ?? '';
    $formId = $formId ?? null;
    $status = $status ?? '';
    $unreadCount = $unreadCount ?? 0;
    $hasFilters = $q !== '' || $formId || $status !== '';
    $qs = function (array $extra = []) use ($q, $formId, $status) {
        return route('admin.form-submissions.index', array_filter(
            array_merge(['q' => $q, 'form_id' => $formId, 'status' => $status], $extra),
            fn ($v) => $v !== '' && $v !== null
        ));
    };
    $fmtTime = function ($dt) {
        if (! $dt) {
            return '—';
        }
        if ($dt->isToday()) {
            return '今天 '.$dt->format('H:i');
        }
        if ($dt->isYesterday()) {
            return '昨天 '.$dt->format('H:i');
        }

        return $dt->year === now()->year ? $dt->format('m-d H:i') : $dt->format('Y-m-d');
    };
@endphp
@section('header_actions')
    @if($unreadCount > 0)
        <span class="badge badge-warn">未读 {{ $unreadCount }}</span>
    @endif
    <a class="btn btn-muted btn-sm" href="{{ route('admin.forms.index') }}">表单定义</a>
    @if($submissions->total() > 0 || $hasFilters)
        <a class="btn btn-muted btn-sm" href="{{ route('admin.form-submissions.export', array_filter(['form_id' => $formId, 'status' => $status, 'q' => $q])) }}">导出 CSV</a>
    @endif
@endsection
@section('content')
    <div class="entry-index inbox-index">
        <div class="entry-find">
            <form method="get" class="filter-bar" id="inboxFindForm" action="{{ route('admin.form-submissions.index') }}">
                @if($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <input type="search" name="q" value="{{ $q }}" placeholder="搜索姓名、留言或邮箱" aria-label="搜索留言">
                <button class="btn btn-muted" type="submit">搜索</button>
                @if($forms->isNotEmpty())
                    <span class="status-label">表单</span>
                    <select name="form_id" onchange="this.form.submit()" aria-label="表单">
                        <option value="">全部</option>
                        @foreach($forms as $f)
                            <option value="{{ $f->id }}" @selected((int) $formId === (int) $f->id)>{{ $f->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            <div class="entry-status-row">
                <span class="status-label">状态</span>
                <a class="chip {{ $status === '' ? 'active' : '' }}" href="{{ $qs(['status' => null]) }}">全部</a>
                <a class="chip {{ $status === 'unread' ? 'active' : '' }}" href="{{ $qs(['status' => 'unread']) }}">未读{{ $unreadCount > 0 ? ' '.$unreadCount : '' }}</a>
                <a class="chip {{ $status === 'replied' ? 'active' : '' }}" href="{{ $qs(['status' => 'replied']) }}">已回复</a>
                @if($hasFilters)
                    <a class="chip" href="{{ route('admin.form-submissions.index') }}">清除筛选</a>
                @endif
            </div>
        </div>

        @if($submissions->isEmpty())
            <div class="list-empty">
                @if($hasFilters)
                    <p>没有符合条件的留言。</p>
                    <a class="btn btn-muted" href="{{ route('admin.form-submissions.index') }}">清除筛选</a>
                @else
                    <p>还没有留言。</p>
                    <p class="muted">前台表单提交后会出现在这里。可以回复到访客邮箱，或只留内部备注。</p>
                    <a class="btn btn-muted" href="{{ route('admin.forms.index') }}">去表单定义</a>
                @endif
            </div>
        @else
            <p class="muted recycle-lead">点摘要打开详情。打开后会标成已读。未读和已回复会打标记。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>留言</th>
                    <th style="width:160px">时间</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($submissions as $row)
                    <tr @class(['is-unread' => ! $row->is_read && ! $row->replied_at])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.form-submissions.show', $row) }}">{{ $row->preview() }}</a>
                                @if($row->replied_at)
                                    <span class="badge badge-ok">已回复</span>
                                @elseif(! $row->is_read)
                                    <span class="badge badge-warn">未读</span>
                                @endif
                            </div>
                            <div class="entry-row-meta">
                                {{ $row->form?->name ?: '未知表单' }}
                                @if($row->ip)
                                    · {{ $row->ip }}
                                @endif
                            </div>
                        </td>
                        <td>{{ $fmtTime($row->created_at) }}</td>
                        <td class="actions">
                            <form action="{{ route('admin.form-submissions.destroy', $row) }}" method="post" onsubmit="return confirm(@json('确定删除这条留言？'))">
                                @csrf @method('DELETE')
                                <input type="hidden" name="keep_form_id" value="{{ $formId }}">
                                <input type="hidden" name="keep_status" value="{{ $status }}">
                                <input type="hidden" name="keep_q" value="{{ $q }}">
                                <button class="btn-quiet" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $submissions->total() }} 条</p>
            {{ $submissions->links() }}
        @endif
    </div>
@endsection
