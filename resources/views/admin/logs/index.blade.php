@extends('admin.layout')
@section('title', '操作日志')
@section('icon', 'clipboard-list')
@section('content')
    <p class="muted">记录后台 POST/PUT/DELETE 写操作。</p>
    <table class="data">
        <thead><tr><th>ID</th><th>管理员</th><th>方法</th><th>路径</th><th>状态</th><th>IP</th><th>时间</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->admin_name ?: ('#'.$log->admin_id) }}</td>
                <td><span class="badge">{{ $log->method }}</span></td>
                <td>{{ $log->path }}</td>
                <td>{{ $log->status }}</td>
                <td>{{ $log->ip }}</td>
                <td>{{ $log->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">暂无日志</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
@endsection
