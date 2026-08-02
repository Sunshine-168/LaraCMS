@extends('admin.layout')
@section('title', '配置快捷面板')
@section('icon', 'th')
@section('content')
    <p class="muted">勾选显示在仪表盘的快捷入口（仅列出你有权限的模块）。全部取消勾选并保存将恢复系统默认组合。</p>
    <form method="post" action="{{ route('admin.quick-panels.update') }}">
        @csrf
        @method('PUT')
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin:16px 0">
            @foreach($options as $opt)
                <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer">
                    <input type="checkbox" name="keys[]" value="{{ $opt['key'] }}" @checked($opt['selected'])>
                    <i class="{{ $opt['icon'] }}" style="width:18px;text-align:center"></i>
                    <span>{{ $opt['title'] }}</span>
                </label>
            @endforeach
        </div>
        <p>
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.dashboard') }}">返回仪表盘</a>
        </p>
    </form>
@endsection
