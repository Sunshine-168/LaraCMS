@extends('admin.layout')
@section('title', '回收站')
@section('icon', 'trash-alt')
@section('content')
    <p class="muted">内容删除后进入回收站，可还原或彻底删除。</p>
    <table class="data">
        <thead><tr><th>ID</th><th>标题</th><th>模型</th><th>删除时间</th><th>操作</th></tr></thead>
        <tbody>
        @forelse($entries as $entry)
            <tr>
                <td>{{ $entry->id }}</td>
                <td>{{ $entry->t('title') }}</td>
                <td>{{ $entry->modelDef?->name }}</td>
                <td>{{ $entry->deleted_at }}</td>
                <td class="actions">
                    <form action="{{ route('admin.recycle.restore', $entry->id) }}" method="post" style="display:inline">@csrf
                        <button class="btn btn-sm" type="submit">还原</button>
                    </form>
                    <form action="{{ route('admin.recycle.force', $entry->id) }}" method="post" style="display:inline" onsubmit="return confirm('彻底删除不可恢复，确认？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">彻底删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">回收站为空</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $entries->links() }}
@endsection
