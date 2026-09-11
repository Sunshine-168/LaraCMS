@extends('admin.layout')
@section('title', '系统工具')
@section('icon', 'tools')
@section('content')
    <h3 style="margin-top:0">清空缓存</h3>
    <p class="muted">清理 Laravel 配置/视图/应用缓存，并失效全页 HTML 缓存。</p>
    <form method="post" action="{{ route('admin.cache.clear') }}" onsubmit="return confirm('确认清空全部缓存？')" style="margin-bottom:28px">
        @csrf
        <button class="btn" type="submit"><i class="fas fa-broom"></i> 一键清空缓存</button>
        <a class="btn btn-muted" href="{{ route('admin.html-cache.edit') }}">仅管理全页缓存</a>
    </form>

    <h3>演示数据</h3>
    <p class="muted">重新执行 CmsSeeder（幂等更新示例栏目/内容/账号）。生产环境请谨慎。</p>
    <form method="post" action="{{ route('admin.cache.seed-demo') }}" onsubmit="return confirm('确认导入/更新演示数据？现有同标识数据会被覆盖更新。')">
        @csrf
        <button class="btn btn-muted" type="submit"><i class="fas fa-database"></i> 导入演示数据</button>
    </form>
    @if($hasLock ?? false)
        <p class="muted" style="margin-top:10px">安装锁：storage/app/install.lock 已存在</p>
    @endif

    <h3 style="margin-top:28px">整站备份</h3>
    <p class="muted">打包数据库 SQL（及 sqlite 文件）与 <code>storage/app/public</code> 上传文件，不含 .env。保留最近 5 份。命令：<code>php artisan cms:backup</code></p>
    <form method="post" action="{{ route('admin.cache.backup') }}" style="margin-bottom:16px">
        @csrf
        <button class="btn" type="submit"><i class="fas fa-archive"></i> 立即备份</button>
    </form>
    @if(!empty($backups))
        <table>
            <thead><tr><th>文件</th><th>大小</th><th>时间</th><th></th></tr></thead>
            <tbody>
            @foreach($backups as $b)
                <tr>
                    <td>{{ $b['file'] }}</td>
                    <td>{{ number_format(($b['size'] ?? 0) / 1024, 1) }} KB</td>
                    <td>{{ date('Y-m-d H:i:s', $b['mtime'] ?? 0) }}</td>
                    <td><a href="{{ route('admin.cache.backup.download', $b['file']) }}">下载</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
