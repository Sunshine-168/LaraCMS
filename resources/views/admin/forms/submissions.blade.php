@extends('admin.layout')
@section('title', '留言收件箱')
@section('icon', 'inbox')
@section('header_actions')
    <span class="muted">未读 {{ $unreadCount }}</span>
@endsection
@section('content')
    <div class="toolbar">
        <a class="btn btn-muted" href="{{ route('admin.forms.index') }}"><i class="fas fa-wpforms"></i> 表单定义</a>
        <a class="btn btn-muted" href="{{ route('admin.form-submissions.export', request()->only(['form_id','status'])) }}"><i class="fas fa-file-csv"></i> 导出 CSV</a>
        <form method="get" style="display:flex;gap:8px;align-items:center;margin:0;flex-wrap:wrap">
            <select name="form_id" onchange="this.form.submit()" style="max-width:220px">
                <option value="">全部表单</option>
                @foreach($forms as $f)
                    <option value="{{ $f->id }}" @selected((string)$formId === (string)$f->id)>{{ $f->name }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" style="max-width:140px">
                <option value="" @selected($status === '')>全部状态</option>
                <option value="unread" @selected($status === 'unread')>未读</option>
                <option value="read" @selected($status === 'read')>已读</option>
                <option value="replied" @selected($status === 'replied')>已回复</option>
            </select>
        </form>
    </div>
    <table class="data">
        <thead><tr><th>ID</th><th>状态</th><th>表单</th><th>摘要</th><th>IP</th><th>时间</th><th>操作</th></tr></thead>
        <tbody>
        @forelse($submissions as $row)
            @php
                $preview = collect($row->data ?? [])->map(fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v)->filter()->take(2)->implode(' / ');
            @endphp
            <tr @if(! $row->is_read) style="font-weight:600" @endif>
                <td>{{ $row->id }}</td>
                <td>
                    @if($row->replied_at)
                        <span class="badge badge-ok">已回复</span>
                    @elseif($row->is_read)
                        <span class="badge">已读</span>
                    @else
                        <span class="badge badge-warn">未读</span>
                    @endif
                </td>
                <td>{{ $row->form?->name }}</td>
                <td>{{ \Illuminate\Support\Str::limit($preview, 80) }}</td>
                <td>{{ $row->ip }}</td>
                <td>{{ $row->created_at }}</td>
                <td>
                    <a class="btn btn-sm" href="{{ route('admin.form-submissions.show', $row) }}">查看</a>
                    <form action="{{ route('admin.form-submissions.destroy', $row) }}" method="post" onsubmit="return confirm('确认删除？')" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">暂无留言</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $submissions->links() }}
@endsection
