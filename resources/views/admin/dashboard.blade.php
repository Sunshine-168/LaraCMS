@extends('admin.layout')
@section('title', '仪表盘')
@section('fullwidth', true)
@section('content')
    @if($kpis !== [])
        <div class="dash-hero {{ $modules['traffic'] ? '' : 'solo' }}">
            <div class="stat-grid dash">
                @foreach($kpis as $card)
                    <a class="stat-card" href="{{ $card['href'] }}">
                        <div>
                            <div class="label">{{ $card['label'] }}</div>
                            <div class="value">{{ $card['value'] }}</div>
                            @if($card['delta'])
                                <div class="delta {{ $card['delta']['dir'] }}">{{ $card['delta']['text'] }}</div>
                            @endif
                            @if($card['hint'])
                                <div class="hint">{{ $card['hint'] }}</div>
                            @endif
                        </div>
                        <div class="icon {{ $card['color'] }}"><i class="fas {{ $card['icon'] }}"></i></div>
                    </a>
                @endforeach
            </div>
            @if($modules['traffic'])
                <a class="card card-panel dash-spark" href="{{ route('admin.stats.index') }}">
                    <div class="card-header"><span>近 7 日 PV / UV</span></div>
                    <div class="card-body">
                        @if($spark['pv'] !== '')
                            <svg class="spark" viewBox="0 0 {{ $spark['width'] }} {{ $spark['height'] }}" preserveAspectRatio="none">
                                <polyline points="{{ $spark['pv'] }}" fill="none" stroke="#007bff" stroke-width="2"/>
                                <polyline points="{{ $spark['uv'] }}" fill="none" stroke="#28a745" stroke-width="2"/>
                            </svg>
                            <p class="muted spark-legend"><span class="dot blue"></span> PV <span class="dot green"></span> UV · 点进统计</p>
                        @else
                            <p class="muted" style="margin:0">还没有足够的访问数据画趋势</p>
                        @endif
                    </div>
                </a>
            @endif
        </div>
    @endif

    <div class="dash-work">
        @if($modules['todos'])
            <div class="card card-panel" id="dash-todos">
                <div class="card-header">
                    <span>待我处理</span>
                    @if($todoCounts['total'] > 0)
                        <span class="badge badge-warn">{{ $todoCounts['total'] }}</span>
                    @endif
                </div>
                @if($todos !== [])
                    @foreach($todos as $todo)
                        <div class="todo-row">
                            <span class="badge {{ $todo['kind'] === 'entry' ? 'badge-warn' : ($todo['kind'] === 'form' ? 'badge-search' : 'badge-tool') }}">{{ $todo['kind_label'] }}</span>
                            <div class="grow">
                                <div>{{ $todo['title'] }}</div>
                                <div class="muted">{{ optional($todo['time'])->diffForHumans() }}</div>
                            </div>
                            <a class="btn btn-sm" href="{{ $todo['url'] }}">去处理</a>
                        </div>
                    @endforeach
                @else
                    <div class="card-body">
                        @php
                            $emptyBits = [];
                            if (auth('admin')->user()?->canPerm('entries')) { $emptyBits[] = '待审稿'; }
                            if (auth('admin')->user()?->canPerm('form_submissions')) { $emptyBits[] = '未读留言'; }
                            if (auth('admin')->user()?->canPerm('comments')) { $emptyBits[] = '待审评论'; }
                        @endphp
                        <p class="muted" style="margin:0">当前没有{{ implode('、', $emptyBits) }}。</p>
                    </div>
                @endif
            </div>
        @endif

        <div class="dash-side">
            @if($modules['activity'])
                <div class="card card-panel">
                    <div class="card-header"><span>最近动态</span></div>
                    @forelse($activity as $row)
                        <div class="todo-row">
                            <span class="badge">{{ $row['kind_label'] }}</span>
                            <div class="grow">
                                @if($row['url'])
                                    <a href="{{ $row['url'] }}">{{ $row['title'] }}</a>
                                @else
                                    {{ $row['title'] }}
                                @endif
                                <div class="muted">{{ optional($row['time'])->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="card-body">
                            <p class="muted" style="margin:0">近 24 小时没有新发布或采集写入。</p>
                        </div>
                    @endforelse
                </div>
            @endif

            <div class="card card-panel">
                <div class="card-header"><span>异常</span></div>
                @forelse($alerts as $alert)
                    <div class="todo-row">
                        <span class="badge {{ $alert['level'] === 'warn' ? 'badge-warn' : 'badge-off' }}">{{ $alert['level'] === 'warn' ? '注意' : '提示' }}</span>
                        <div class="grow">
                            @if($alert['url'])
                                <a href="{{ $alert['url'] }}">{{ $alert['title'] }}</a>
                            @else
                                {{ $alert['title'] }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card-body">
                        <p class="muted" style="margin:0">暂时没有需要盯的异常。</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">
            <span>接下来做什么</span>
            <a class="btn btn-sm btn-muted" href="{{ route('admin.quick-panels.edit') }}">配置</a>
        </div>
        <div class="card-body">
            <div class="dash-actions">
                @forelse($quickNav as $item)
                    <a class="btn {{ $item['key'] === 'entries_write' ? '' : 'btn-muted' }}" href="{{ $item['url'] }}">
                        <i class="{{ $item['icon'] }}"></i> {{ $item['title'] }}
                    </a>
                @empty
                    <p class="muted" style="margin:0">暂无快捷入口，请先 <a href="{{ route('admin.quick-panels.edit') }}">配置</a>。</p>
                @endforelse
            </div>
            @if($quickOverflow > 0)
                <p class="muted" style="margin:10px 0 0">还有 {{ $quickOverflow }} 个入口在配置里，首页只保留高频动作。</p>
            @endif
        </div>
    </div>

    @if($health)
        <details class="dash-health" @if($health['attention']) open @endif>
            <summary>
                <span>站点健康</span>
                <span class="muted" style="font-weight:400">
                    缓存 {{ $health['cache'] ? '开' : '关' }}
                    · 采集 {{ $health['collect_mode'] }}
                    @if($health['last_run'])
                        · 上次 {{ $health['last_ok'] ? '成功' : '失败' }}
                    @endif
                </span>
                @if($health['attention'])
                    <span class="badge badge-warn">需要看一眼</span>
                @else
                    <span class="badge badge-ok">正常</span>
                @endif
            </summary>
            <table class="data info" style="border:0">
                <tr>
                    <th>主题</th>
                    <td>{{ $health['theme'] }}</td>
                    <th>全页缓存</th>
                    <td>
                        @if($health['cache'])
                            <span class="badge badge-ok">已启用</span>
                        @else
                            <span class="badge badge-off">未启用</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>采集模式</th>
                    <td><span class="badge">{{ $health['collect_mode'] }}</span></td>
                    <th>上次采集</th>
                    <td>
                        @if($health['last_run'])
                            {{ $health['last_run'] }}
                            @if($health['last_ok'])
                                <span class="badge badge-ok">成功</span>
                            @else
                                <span class="badge badge-warn">失败</span>
                            @endif
                            @if($health['last_message'])
                                <div class="muted">{{ $health['last_message'] }}</div>
                            @endif
                        @else
                            <span class="muted">还没有采集记录</span>
                        @endif
                    </td>
                </tr>
                @if($health['schedule'])
                    <tr>
                        <th>调度</th>
                        <td colspan="3">{{ $health['schedule'] }}</td>
                    </tr>
                @endif
            </table>
        </details>
    @endif
@endsection
