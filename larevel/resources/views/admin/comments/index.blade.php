@extends('admin.layout')
@section('title', '评论审核')
@section('icon', 'comments')
@section('header_actions')
    <span class="muted">待审 {{ $pendingCount }}</span>
@endsection
@section('content')
    <div class="toolbar">
        <form method="get" style="display:flex;gap:8px;align-items:center;margin:0;flex-wrap:wrap">
            <select name="status" onchange="this.form.submit()" style="max-width:160px">
                @foreach(['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝','spam'=>'垃圾','all'=>'全部'] as $k => $label)
                    <option value="{{ $k }}" @selected((string)$status === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="number" name="entry_id" value="{{ $entryId }}" placeholder="内容 ID" style="max-width:120px">
            <button class="btn btn-sm btn-muted" type="submit">筛选</button>
        </form>
    </div>

    <form method="post" action="{{ route('admin.comments.batch') }}" id="batchForm" style="margin-top:8px">
        @csrf
        <div class="toolbar">
            <select name="status" required style="max-width:140px">
                <option value="approved">批量通过</option>
                <option value="rejected">批量拒绝</option>
                <option value="spam">批量标垃圾</option>
                <option value="pending">批量待审</option>
            </select>
            <button class="btn btn-sm" type="submit" onclick="return confirm('确认批量处理所选评论？')">执行</button>
        </div>
    </form>

    <table class="data">
        <thead>
        <tr>
            <th style="width:36px"><input type="checkbox" onclick="document.querySelectorAll('.cid').forEach(c=>c.checked=this.checked)"></th>
            <th>ID</th>
            <th>内容</th>
            <th>作者</th>
            <th>评论</th>
            <th>状态</th>
            <th>IP / 时间</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        @forelse($comments as $row)
            <tr>
                <td><input class="cid" form="batchForm" type="checkbox" name="ids[]" value="{{ $row->id }}"></td>
                <td>{{ $row->id }}</td>
                <td>
                    @if($row->entry)
                        <a href="{{ url($row->entry->url) }}" target="_blank">{{ $row->entry->t('title') }}</a>
                        <div class="muted">#{{ $row->entry_id }}</div>
                    @else
                        #{{ $row->entry_id }}
                    @endif
                    @if($row->parent_id)
                        <div class="muted">回复 #{{ $row->parent_id }}</div>
                    @endif
                </td>
                <td>
                    {{ $row->author_name }}
                    @if($row->author_email)<div class="muted">{{ $row->author_email }}</div>@endif
                    @if($row->member_id)<div class="muted">会员 #{{ $row->member_id }}</div>@endif
                </td>
                <td style="max-width:320px;white-space:pre-wrap">{{ $row->content }}</td>
                <td>{{ \App\Models\Cms\Comment::statusLabel($row->status) }}</td>
                <td>
                    {{ $row->ip }}
                    <div class="muted">{{ $row->created_at }}</div>
                </td>
                <td style="white-space:nowrap">
                    @if($row->status !== 'approved')
                        <form action="{{ route('admin.comments.moderate', $row) }}" method="post" style="display:inline">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <button class="btn btn-sm" type="submit">通过</button>
                        </form>
                    @endif
                    @if($row->status !== 'rejected')
                        <form action="{{ route('admin.comments.moderate', $row) }}" method="post" style="display:inline">
                            @csrf
                            <input type="hidden" name="status" value="rejected">
                            <button class="btn btn-sm btn-muted" type="submit">拒绝</button>
                        </form>
                    @endif
                    @if($row->status !== 'spam')
                        <form action="{{ route('admin.comments.moderate', $row) }}" method="post" style="display:inline">
                            @csrf
                            <input type="hidden" name="status" value="spam">
                            <button class="btn btn-sm btn-muted" type="submit">垃圾</button>
                        </form>
                    @endif
                    <form action="{{ route('admin.comments.destroy', $row) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">暂无评论</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $comments->links() }}
@endsection
