@extends('admin.layout')
@section('title', '流量统计')
@section('content')
    <p>
        <a class="btn" href="{{ route('admin.stats.index') }}">流量</a>
        <a class="btn btn-muted" href="{{ route('admin.stats.spiders') }}">蜘蛛</a>
        <a class="btn btn-muted" href="{{ route('admin.stats.logs') }}">明细</a>
    </p>

    <table style="margin-bottom:20px">
        <thead>
        <tr><th>时段</th><th>PV</th><th>UV</th><th>独立 IP</th><th>蜘蛛 PV</th></tr>
        </thead>
        <tbody>
        <tr>
            <td>今日</td>
            <td>{{ $overview['today']['pv'] }}</td>
            <td>{{ $overview['today']['uv'] }}</td>
            <td>{{ $overview['today']['ip'] }}</td>
            <td>{{ $overview['today']['spider_pv'] }}</td>
        </tr>
        <tr>
            <td>昨日</td>
            <td>{{ $overview['yesterday']['pv'] }}</td>
            <td>{{ $overview['yesterday']['uv'] }}</td>
            <td>{{ $overview['yesterday']['ip'] }}</td>
            <td>{{ $overview['yesterday']['spider_pv'] }}</td>
        </tr>
        <tr>
            <td>近 7 天</td>
            <td>{{ $overview['days7']['pv'] }}</td>
            <td>{{ $overview['days7']['uv'] }}</td>
            <td>{{ $overview['days7']['ip'] }}</td>
            <td>{{ $overview['days7']['spider_pv'] }}</td>
        </tr>
        <tr>
            <td>近 30 天</td>
            <td>{{ $overview['days30']['pv'] }}</td>
            <td>{{ $overview['days30']['uv'] }}</td>
            <td>{{ $overview['days30']['ip'] }}</td>
            <td>{{ $overview['days30']['spider_pv'] }}</td>
        </tr>
        </tbody>
    </table>

    <h3>近 14 日趋势</h3>
    <table>
        <thead><tr><th>日期</th><th>PV</th><th>UV</th><th>蜘蛛</th></tr></thead>
        <tbody>
        @foreach($trend as $row)
            <tr>
                <td>{{ $row['day'] }}</td>
                <td>{{ $row['pv'] }}</td>
                <td>{{ $row['uv'] }}</td>
                <td>{{ $row['spider_pv'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <form method="get" style="margin:20px 0;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        <div>
            <label>从</label>
            <input type="date" name="from" value="{{ $from }}">
        </div>
        <div>
            <label>到</label>
            <input type="date" name="to" value="{{ $to }}">
        </div>
        <button class="btn" type="submit">筛选热门/来路</button>
    </form>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div>
            <h3>热门页面</h3>
            <table>
                <thead><tr><th>路径</th><th>PV</th></tr></thead>
                <tbody>
                @forelse($topPages as $p)
                    <tr><td>{{ $p->path }}</td><td>{{ $p->hits }}</td></tr>
                @empty
                    <tr><td colspan="2" class="muted">暂无数据</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>
            <h3>来路 Top</h3>
            <table>
                <thead><tr><th>Referer</th><th>次数</th></tr></thead>
                <tbody>
                @forelse($topReferers as $r)
                    <tr><td style="word-break:break-all">{{ $r->referer }}</td><td>{{ $r->hits }}</td></tr>
                @empty
                    <tr><td colspan="2" class="muted">暂无数据</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
