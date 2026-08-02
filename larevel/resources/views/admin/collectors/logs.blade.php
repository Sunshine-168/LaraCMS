@extends('admin.layout')
@section('title', '采集日志')
@section('content')
    <p><a class="btn btn-muted" href="{{ route('admin.collectors.index') }}">返回采集器</a></p>
    <table>
        <thead><tr><th>ID</th><th>采集器</th><th>状态</th><th>拉取</th><th>新建</th><th>跳过</th><th>消息</th><th>时间</th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->collector?->name }}</td>
                <td>{{ $log->status }}</td>
                <td>{{ $log->fetched }}</td>
                <td>{{ $log->created }}</td>
                <td>{{ $log->skipped }}</td>
                <td class="muted">{{ $log->message }}</td>
                <td>{{ $log->created_at }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
@endsection
