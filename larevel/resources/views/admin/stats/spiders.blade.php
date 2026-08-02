@extends('admin.layout')
@section('title', '蜘蛛统计')
@section('content')
    <p>
        <a class="btn btn-muted" href="{{ route('admin.stats.index') }}">流量</a>
        <a class="btn" href="{{ route('admin.stats.spiders') }}">蜘蛛</a>
        <a class="btn btn-muted" href="{{ route('admin.stats.logs') }}">明细</a>
    </p>

    <p class="muted">今日蜘蛛 PV：{{ $overview['today']['spider_pv'] }} · 近 7 天：{{ $overview['days7']['spider_pv'] }} · 近 30 天：{{ $overview['days30']['spider_pv'] }}</p>

    <form method="get" style="margin:16px 0;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        <div>
            <label>从</label>
            <input type="date" name="from" value="{{ $from }}">
        </div>
        <div>
            <label>到</label>
            <input type="date" name="to" value="{{ $to }}">
        </div>
        <button class="btn" type="submit">筛选</button>
    </form>

    <h3>蜘蛛汇总</h3>
    <table>
        <thead><tr><th>蜘蛛</th><th>抓取次数</th><th>独立 IP</th></tr></thead>
        <tbody>
        @forelse($summary as $row)
            <tr>
                <td>{{ $row->spider_name ?: 'Unknown' }}</td>
                <td>{{ $row->hits }}</td>
                <td>{{ $row->ips }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">该时段暂无蜘蛛访问</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3 style="margin-top:24px">蜘蛛按日明细</h3>
    <table>
        <thead><tr><th>日期</th><th>蜘蛛</th><th>次数</th></tr></thead>
        <tbody>
        @forelse($daily as $row)
            <tr>
                <td>{{ $row->day }}</td>
                <td>{{ $row->spider_name }}</td>
                <td>{{ $row->hits }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">暂无</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3 style="margin-top:24px">最近蜘蛛抓取</h3>
    <table>
        <thead><tr><th>时间</th><th>蜘蛛</th><th>路径</th><th>IP</th><th>状态</th></tr></thead>
        <tbody>
        @foreach($recent as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->spider_name }}</td>
                <td>{{ $log->path }}</td>
                <td>{{ $log->ip }}</td>
                <td>{{ $log->status_code }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
