@extends('admin.layout')
@php
    $formName = $submission->form?->name ?: '留言';
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
@section('title', $formName)
@section('icon', 'inbox')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.form-submissions.index') }}">返回收件箱</a>
@endsection
@section('content')
    <div class="inbox-show">
        <div class="entry-row-title-line">
            <span class="entry-row-title">{{ $submission->preview(120) }}</span>
            @if($submission->replied_at)
                <span class="badge badge-ok">已回复</span>
            @endif
        </div>
        <p class="muted recycle-lead">
            {{ $formName }}
            · {{ $fmtTime($submission->created_at) }}
            @if($submission->ip)
                · {{ $submission->ip }}
            @endif
            @if($submission->replied_at)
                · 回复于 {{ $fmtTime($submission->replied_at) }}
            @endif
        </p>

        <h3>提交内容</h3>
        <table class="data">
            <tbody>
            @forelse(($submission->data ?? []) as $k => $v)
                <tr>
                    <th style="width:140px">{{ $submission->fieldLabel((string) $k) }}</th>
                    <td style="white-space:pre-wrap">{{ is_array($v) ? implode(', ', $v) : $v }}</td>
                </tr>
            @empty
                <tr><td class="muted">没有字段内容。</td></tr>
            @endforelse
            </tbody>
        </table>

        <h3>回复</h3>
        @if($submission->guestEmail())
            <p class="muted field-hint">将发到 {{ $submission->guestEmail() }}。需要先在站点设置里配好 SMTP。</p>
        @else
            <p class="muted field-hint">这条没有邮箱，只能留内部备注。</p>
        @endif
        <form method="post" class="inbox-reply" action="{{ route('admin.form-submissions.update', $submission) }}">
            @csrf
            @method('PUT')
            <label for="inbox-note">备注 / 回复正文</label>
            <textarea id="inbox-note" name="admin_note" rows="6">{{ old('admin_note', $submission->admin_note) }}</textarea>
            <div class="form-actions">
                <button class="btn" type="submit">保存备注</button>
                @if($submission->guestEmail())
                    <button class="btn btn-muted" type="submit" name="send_reply" value="1">保存并发送回复</button>
                @endif
                <button class="btn-quiet" type="submit" form="unreadForm">标为未读</button>
            </div>
        </form>
        <form id="unreadForm" method="post" action="{{ route('admin.form-submissions.unread', $submission) }}">@csrf</form>
    </div>
@endsection
