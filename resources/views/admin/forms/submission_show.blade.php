@extends('admin.layout')
@section('title', '留言 #'.$submission->id)
@section('icon', 'inbox')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.form-submissions.index') }}">返回列表</a>
@endsection
@section('content')
    <p class="muted">
        表单：{{ $submission->form?->name ?: '—' }}
        · IP {{ $submission->ip ?: '—' }}
        · {{ $submission->created_at }}
        @if($submission->replied_at)
            · 已回复 {{ $submission->replied_at }}
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
            <tr><td class="muted">无字段</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3 style="margin-top:24px">处理 / 回复</h3>
    @if($submission->guestEmail())
        <p class="muted">将回复到：{{ $submission->guestEmail() }}（需配置站点 SMTP）</p>
    @else
        <p class="muted">这条留言没有邮箱，只能留内部备注。</p>
    @endif
    <form method="post" action="{{ route('admin.form-submissions.update', $submission) }}">
        @csrf
        @method('PUT')
        <label>备注 / 回复正文</label>
        <textarea name="admin_note" rows="6">{{ old('admin_note', $submission->admin_note) }}</textarea>
        <p style="margin-top:12px">
            <button class="btn" type="submit">保存备注</button>
            @if($submission->guestEmail())
                <button class="btn btn-muted" type="submit" name="send_reply" value="1">保存并发送回复</button>
            @endif
            <a class="btn btn-muted" href="{{ route('admin.form-submissions.unread', $submission) }}"
               onclick="event.preventDefault(); document.getElementById('unreadForm').submit();">标为未读</a>
        </p>
    </form>
    <form id="unreadForm" method="post" action="{{ route('admin.form-submissions.unread', $submission) }}" style="display:none">@csrf</form>
@endsection
