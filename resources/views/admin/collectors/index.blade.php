@extends('admin.layout')
@section('title', '采集任务')
@section('icon', 'cloud-download-alt')
@section('content')
    <div class="toolbar">
        <a class="btn" href="{{ route('admin.collectors.create') }}"><i class="fas fa-plus"></i> 新建采集器</a>
        <a class="btn btn-muted" href="{{ route('admin.collectors.logs') }}"><i class="fas fa-history"></i> 运行日志</a>
    </div>
    <p class="muted" style="margin-top:0">
        支持 RSS/Atom、JSON 列表、HTML 选择器（仅列表页 + 同源详情，不翻页）。当前模式：<strong>{{ $collectMode }}</strong>
        @if($collectMode === 'queue')
            （队列 <code>{{ $collectQueue }}</code>，需 <code>queue:work --queue={{ $collectQueue }}</code>）
        @elseif($collectMode === 'schedule')
            （定时同步，需 cron / <code>schedule:work</code>）
        @else
            （自动调度已关，仅手动）
        @endif
        · <code>CMS_COLLECT_MODE=schedule|queue|off</code>
    </p>
    <table class="data">
        <thead>
        <tr><th>ID</th><th>名称</th><th>类型</th><th>间隔</th><th>目标</th><th>上次</th><th>状态</th><th>操作</th></tr>
        </thead>
        <tbody>
        @forelse($collectors as $c)
            <tr>
                <td>{{ $c->id }}</td>
                <td>{{ $c->name }}</td>
                <td><span class="badge">{{ $c->type }}</span></td>
                <td>{{ $c->interval_minutes }} 分</td>
                <td>{{ $c->modelDef?->name }} / {{ $c->category?->t('title') }}</td>
                <td class="muted">{{ $c->last_run_at }}<br>{{ \Illuminate\Support\Str::limit($c->last_message, 40) }}</td>
                <td>
                    @if(! $c->is_active)
                        <span class="badge badge-off">停用</span>
                    @elseif(($c->last_status ?? '') === 'ok')
                        <span class="badge badge-ok">ok</span>
                    @elseif(($c->last_status ?? '') === 'fail')
                        <span class="badge badge-warn">fail</span>
                    @else
                        <span class="badge">待运行</span>
                    @endif
                </td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.collectors.edit', $c) }}">编辑</a>
                    <form action="{{ route('admin.collectors.run', $c) }}" method="post">@csrf
                        <button class="btn btn-sm" type="submit">立即采集</button>
                    </form>
                    <form action="{{ route('admin.collectors.destroy', $c) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">暂无采集器</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $collectors->links() }}
@endsection
