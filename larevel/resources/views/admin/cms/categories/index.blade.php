@extends('admin.layout')

@php
    $pageTitle = '栏目管理';
    $pageDescription = '当前先迁移栏目列表读取，后续再补新增、编辑、删除和权限配置。';
@endphp

@section('content')
    <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div class="meta">内容语言：{{ $lang }}</div>
            <div style="display:flex;gap:12px;align-items:center;">
                <div class="meta">模型数：{{ $models->count() }}</div>
                <a class="btn" href="{{ route('admin.categories.create', ['clang' => $lang]) }}">新增栏目</a>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>栏目名称</th>
                    <th>目录名</th>
                    <th>类型</th>
                    <th>模型</th>
                    <th>状态</th>
                    <th>导航</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $row['level']) !!}{{ $row['title'] }}</td>
                        <td><span class="badge">{{ $row['name'] }}</span></td>
                        <td>{{ $row['type_text'] }}</td>
                        <td>{{ $row['model_name'] ?: '-' }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ (int) $row['ismenu'] === 1 ? '显示' : '隐藏' }}</td>
                        <td>{{ $row['weigh'] }}</td>
                        <td>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a class="badge" href="{{ route('admin.categories.edit', $row['id']) }}">编辑</a>
                                @if((int) $row['model_id'] > 0)
                                    <a class="badge" href="{{ route('admin.contents.index', ['category_id' => $row['id'], 'model_id' => $row['model_id']]) }}">内容</a>
                                @endif
                                <form method="post" action="{{ route('admin.categories.destroy', $row['id']) }}" onsubmit="return confirm('确认删除该栏目吗？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="badge" style="border:0;cursor:pointer;background:#fef2f2;color:#b91c1c;">删除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="meta">暂无栏目数据，或当前管理员没有栏目权限。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
