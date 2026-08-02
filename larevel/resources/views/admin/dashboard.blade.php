@extends('admin.layout')
@section('title', '仪表盘')
@section('fullwidth', true)
@section('content')
    <div class="stat-grid">
        <div class="stat-card">
            <div>
                <div class="label">今日 PV</div>
                <div class="value">{{ $traffic['pv'] }}</div>
            </div>
            <div class="icon blue"><i class="fas fa-eye"></i></div>
        </div>
        <div class="stat-card">
            <div>
                <div class="label">今日 UV</div>
                <div class="value">{{ $traffic['uv'] }}</div>
            </div>
            <div class="icon green"><i class="fas fa-user"></i></div>
        </div>
        <div class="stat-card">
            <div>
                <div class="label">蜘蛛 PV</div>
                <div class="value">{{ $traffic['spider_pv'] }}</div>
            </div>
            <div class="icon orange"><i class="fas fa-spider"></i></div>
        </div>
        <div class="stat-card">
            <div>
                <div class="label">内容总数</div>
                <div class="value">{{ $counts['entries'] }}</div>
            </div>
            <div class="icon purple"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">
            <span><i class="fas fa-th"></i> 快捷导航</span>
        </div>
        <div class="card-body">
            <div class="quick-grid">
                @foreach($quickNav as $item)
                    <div class="quick">
                        <a href="{{ $item['url'] }}">
                            <span class="icon"><i class="{{ $item['icon'] }}"></i></span>
                            <span class="text">{{ $item['title'] }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">
            <span><i class="fas fa-chart-bar"></i> 内容概览</span>
            <a class="btn btn-sm btn-muted" href="{{ route('admin.stats.index') }}">查看统计</a>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data" style="border:0">
                <thead>
                <tr>
                    <th>模块</th><th>数量</th><th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>内容 Entry</td><td>{{ $counts['entries'] }}</td>
                    <td><a href="{{ route('admin.entries.index') }}">管理</a></td>
                </tr>
                <tr>
                    <td>栏目</td><td>{{ $counts['categories'] }}</td>
                    <td><a href="{{ route('admin.categories.index') }}">管理</a></td>
                </tr>
                <tr>
                    <td>模型</td><td>{{ $counts['models'] }}</td>
                    <td>
                        @if(auth('admin')->user()?->canPerm('models'))
                            <a href="{{ route('admin.models.index') }}">管理</a>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>媒体</td><td>{{ $counts['media'] }}</td>
                    <td><a href="{{ route('admin.media.index') }}">管理</a></td>
                </tr>
                <tr>
                    <td>留言</td><td>{{ $counts['forms'] }}</td>
                    <td><a href="{{ route('admin.form-submissions.index') }}">收件箱</a></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">
            <span><i class="fas fa-server"></i> 系统信息</span>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data info" style="border:0">
                <tr>
                    <th>站点名称</th>
                    <td>{{ $system['site_name'] }}</td>
                    <th>当前主题</th>
                    <td>{{ $system['theme'] }}</td>
                </tr>
                <tr>
                    <th>Laravel</th>
                    <td>{{ $system['laravel'] }}</td>
                    <th>PHP</th>
                    <td>{{ $system['php'] }}</td>
                </tr>
                <tr>
                    <th>采集模式</th>
                    <td><span class="badge">{{ $system['collect_mode'] }}</span></td>
                    <th>全页缓存</th>
                    <td>
                        @if($system['html_cache'])
                            <span class="badge badge-ok">已启用</span>
                        @else
                            <span class="badge badge-off">未启用</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endsection
