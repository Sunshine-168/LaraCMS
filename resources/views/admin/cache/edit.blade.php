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
@endsection
