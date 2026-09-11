@extends('admin.layout')
@section('title', '访问明细')
@section('content')
    <p>
        <a class="btn btn-muted" href="{{ route('admin.stats.index') }}">流量</a>
        <a class="btn btn-muted" href="{{ route('admin.stats.spiders') }}">蜘蛛</a>
        <a class="btn" href="{{ route('admin.stats.logs') }}">明细</a>
    </p>

    <form method="get" style="margin:16px 0;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        <div>
            <label>类型</label>
            <select name="spider">
                <option value="">全部</option>
                <option value="0" @selected(($filters['spider'] ?? '') === '0')>人类</option>
                <option value="1" @selected(($filters['spider'] ?? '') === '1')>蜘蛛</option>
            </select>
        </div>
        <div>
            <label>路径包含</label>
            <input type="text" name="path" value="{{ $filters['path'] ?? '' }}" placeholder="/news">
        </div>
        <button class="btn" type="submit">筛选</button>
    </form>

    <table>
        <thead>
        <tr><th>时间</th><th>类型</th><th>路径</th><th>IP</th><th>来路</th><th>UA</th><th>状态</th></tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->is_spider ? ($log->spider_name ?: 'Bot') : '人类' }}</td>
                <td>{{ $log->path }}@if($log->query)?{{ $log->query }}@endif</td>
                <td>{{ $log->ip }}</td>
                <td class="muted" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $log->referer }}</td>
                <td class="muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $log->user_agent }}">{{ $log->user_agent }}</td>
                <td>{{ $log->status_code }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
@endsection
