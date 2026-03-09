@extends('admin.layout')

@php
    $typeText = [1 => '图集', 2 => '多媒体', 3 => 'HTML', 4 => '内容数据', 5 => '文字链接'];
@endphp

@section('content')
    <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div class="meta">内容语言：{{ $lang }}</div>
            <a class="btn" href="{{ route('admin.recommends.create', ['clang' => $lang]) }}">新增推荐位</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>标识</th>
                    <th>标题</th>
                    <th>类型</th>
                    <th>状态</th>
                    <th>排序</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><span class="badge">{{ $row->name }}</span></td>
                        <td>{{ $row->remark ?: '-' }}</td>
                        <td>{{ $typeText[(int) $row->type] ?? $row->type }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->weigh }}</td>
                        <td>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a class="badge" href="{{ route('admin.recommends.edit', $row) }}">编辑</a>
                                <form method="post" action="{{ route('admin.recommends.destroy', $row) }}" onsubmit="return confirm('确认删除该推荐位吗？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="badge" style="border:0;cursor:pointer;background:#fef2f2;color:#b91c1c;">删除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="meta">暂无推荐位数据。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
