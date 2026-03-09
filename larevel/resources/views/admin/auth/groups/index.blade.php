@extends('admin.layout')

@section('content')
    <section class="card" style="margin-bottom:16px;">
        <a href="{{ route('admin.auth-groups.create') }}" class="btn">新增角色组</a>
    </section>

    <section class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>备注</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ str_repeat(' - ', $row['level']) }}{{ $row['name'] }}</td>
                        <td>{{ $row['remark'] ?: '-' }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ !empty($row['create_time']) ? date('Y-m-d H:i:s', (int) $row['create_time']) : '-' }}</td>
                        <td>
                            @if(($row['rules'] ?? '') !== '*')
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a class="badge" href="{{ route('admin.auth-groups.edit', $row['id']) }}">编辑</a>
                                    <form method="post" action="{{ route('admin.auth-groups.destroy', $row['id']) }}" onsubmit="return confirm('确认删除该角色组吗？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="badge" style="border:0;cursor:pointer;background:#fef2f2;color:#b91c1c;">删除</button>
                                    </form>
                                </div>
                            @else
                                <span class="meta">系统角色</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="meta">暂无角色组数据。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
