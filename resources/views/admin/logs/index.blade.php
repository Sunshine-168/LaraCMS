@extends('admin.layout')
@section('title', admin_t('nav.logs'))
@section('icon', 'clipboard-list')
@php
    $q = $q ?? '';
    $ip = $ip ?? '';
    $mine = (bool) ($mine ?? false);
    $from = $from ?? '';
    $to = $to ?? '';
    $today = $today ?? now()->toDateString();
    $yesterday = $yesterday ?? now()->subDay()->toDateString();
    $weekFrom = $weekFrom ?? now()->subDays(6)->toDateString();
    $monthFrom = $monthFrom ?? now()->subDays(29)->toDateString();
    $hasDate = $from !== '' || $to !== '';
    $hasFilters = $q !== '' || $ip !== '' || $mine || $hasDate;
    $qs = function (array $extra = []) use ($q, $ip, $mine, $from, $to) {
        return route('admin.logs.index', array_filter(
            array_merge([
                'q' => $q !== '' ? $q : null,
                'ip' => $ip !== '' ? $ip : null,
                'mine' => $mine ? '1' : null,
                'from' => $from !== '' ? $from : null,
                'to' => $to !== '' ? $to : null,
            ], $extra),
            fn ($v) => $v !== '' && $v !== null
        ));
    };
    $fmtTime = function ($dt) {
        if (! $dt) {
            return '—';
        }
        $clock = $dt->format('H:i:s');
        if ($dt->isToday()) {
            return '今天 '.$clock;
        }
        if ($dt->isYesterday()) {
            return '昨天 '.$clock;
        }

        return $dt->year === now()->year ? $dt->format('m-d ').$clock : $dt->format('Y-m-d ').$clock;
    };
@endphp
@section('content')
    <div class="entry-index log-index">
        <div class="entry-find">
            <form method="get" class="filter-bar log-find" action="{{ route('admin.logs.index') }}">
                @if($mine)
                    <input type="hidden" name="mine" value="1">
                @endif
                @if($ip !== '')
                    <input type="hidden" name="ip" value="{{ $ip }}">
                @endif
                <input type="search" name="q" value="{{ $q }}" placeholder="搜索管理员、IP 或改动内容" aria-label="搜索日志">
                <div class="field">
                    <label for="log-from">从</label>
                    <input id="log-from" type="date" name="from" value="{{ $from }}" aria-label="开始日期">
                </div>
                <div class="field">
                    <label for="log-to">到</label>
                    <input id="log-to" type="date" name="to" value="{{ $to }}" aria-label="结束日期">
                </div>
                <button class="btn btn-muted" type="submit">查询</button>
            </form>
            <div class="entry-status-row">
                <a class="chip {{ ! $mine ? 'active' : '' }}" href="{{ $qs(['mine' => null]) }}">全部</a>
                <a class="chip {{ $mine ? 'active' : '' }}" href="{{ $qs(['mine' => '1']) }}">我的</a>
                <a class="chip {{ $from === $today && $to === $today ? 'active' : '' }}" href="{{ $qs(['from' => $today, 'to' => $today]) }}">今天</a>
                <a class="chip {{ $from === $yesterday && $to === $yesterday ? 'active' : '' }}" href="{{ $qs(['from' => $yesterday, 'to' => $yesterday]) }}">昨天</a>
                <a class="chip {{ $from === $weekFrom && $to === $today ? 'active' : '' }}" href="{{ $qs(['from' => $weekFrom, 'to' => $today]) }}">近7天</a>
                <a class="chip {{ $from === $monthFrom && $to === $today ? 'active' : '' }}" href="{{ $qs(['from' => $monthFrom, 'to' => $today]) }}">近30天</a>
                @if($ip !== '')
                    <a class="chip active" href="{{ $qs(['ip' => null]) }}" title="取消按这个 IP 筛选">{{ $ip }}</a>
                @endif
                @if($hasDate)
                    <a class="chip" href="{{ $qs(['from' => null, 'to' => null]) }}">全部时间</a>
                @endif
                @if($hasFilters)
                    <a class="chip" href="{{ route('admin.logs.index') }}">清除筛选</a>
                @endif
            </div>
        </div>

        @if($logs->isEmpty())
            <div class="list-empty">
                @if($hasFilters)
                    <p>没有符合条件的记录。</p>
                    <a class="btn btn-muted" href="{{ route('admin.logs.index') }}">清除筛选</a>
                @else
                    <p>还没有操作记录。</p>
                    <p class="muted">打开页面不会记。登录、退出、保存、删除会出现在这里，并写清改了哪一条、IP 是谁。</p>
                @endif
            </div>
        @else
            <table class="data">
                <thead>
                <tr>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $log)
                    @php
                        $subject = $log->subject;
                        $title = $log->actionLabel().($subject ? '「'.$subject.'」' : '');
                        $href = $log->target_href;
                    @endphp
                    <tr @class(['is-off' => $log->failed()])>
                        <td>
                            <div class="entry-row-title-line">
                                @if($href)
                                    <a class="entry-row-title" href="{{ $href }}">{{ $title }}</a>
                                @else
                                    <span class="entry-row-title">{{ $title }}</span>
                                @endif
                                @if($log->failed())
                                    <span class="badge badge-off">没成功</span>
                                @endif
                            </div>
                            <div class="entry-row-meta">
                                @if($log->target_id && ! $subject)
                                    编号 {{ $log->target_id }} ·
                                @endif
                                @if($log->detail && str_starts_with((string) $log->detail, '选了') && ! str_contains((string) $subject, '选了'))
                                    {{ $log->detail }} ·
                                @endif
                                {{ $log->admin_name ?: '未知管理员' }}
                                · {{ $fmtTime($log->created_at) }}
                                @if($log->ip)
                                    · <a class="log-ip" href="{{ $qs(['ip' => $log->ip]) }}">{{ $log->ip }}</a>
                                    @if($log->ip_place)
                                        <span class="log-ip-place">{{ $log->ip_place }}</span>
                                    @endif
                                @endif
                                @if($log->failed())
                                    · 返回 {{ $log->status }}
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $logs->total() }} 条</p>
            {{ $logs->links() }}
        @endif
    </div>
@endsection
