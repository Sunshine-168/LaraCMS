@extends('admin.layout')

@php
    $pageTitle = '控制台';
    $pageDescription = '第一阶段已迁移登录、菜单、控制台和栏目列表。';
@endphp

@section('content')
    <div class="grid grid-3" style="margin-bottom:16px;">
        <section class="card">
            <div class="meta">管理员</div>
            <div class="stats">{{ $stats['admins'] }}</div>
        </section>
        <section class="card">
            <div class="meta">栏目数</div>
            <div class="stats">{{ $stats['categories'] }}</div>
        </section>
        <section class="card">
            <div class="meta">内容数</div>
            <div class="stats">{{ $stats['articles'] }}</div>
        </section>
    </div>

    <div class="grid" style="grid-template-columns: 1.2fr 0.8fr;">
        <section class="card">
            <h3 style="margin-top:0;">快捷入口</h3>
            @forelse($quickPanels as $panel)
                <div style="padding:10px 0;border-bottom:1px solid #e5e7eb;">
                    <a href="{{ $panel->url }}">{{ $panel->title }}</a>
                    <div class="meta">{{ $panel->name }}</div>
                </div>
            @empty
                <div class="meta">当前没有常用菜单。</div>
            @endforelse
        </section>

        <section class="card">
            <h3 style="margin-top:0;">系统信息</h3>
            <div class="meta">操作系统：{{ $systemInfo['OS'] }}</div>
            <div class="meta" style="margin-top:8px;">域名：{{ $systemInfo['domain'] }}</div>
            <div class="meta" style="margin-top:8px;">PHP：{{ $systemInfo['phpv'] }}</div>
            <div class="meta" style="margin-top:8px;">MySQL：{{ $systemInfo['mysqlv'] }}</div>
        </section>
    </div>
@endsection
