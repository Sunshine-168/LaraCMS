@extends('admin.layout')

@section('content')
    <section class="card" style="margin-bottom:16px;">
        <form method="get" action="{{ route('admin.admin-users.index') }}" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <div>
                <label>用户名</label>
                <input name="username" value="{{ request('username') }}" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div>
                <label>昵称</label>
                <input name="nickname" value="{{ request('nickname') }}" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div>
                <label>邮箱</label>
                <input name="email" value="{{ request('email') }}" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <button type="submit" class="btn">筛选</button>
            <a href="{{ route('admin.admin-users.index') }}" class="btn btn-secondary">重置</a>
            <a href="{{ route('admin.admin-users.create') }}" class="btn">新增管理员</a>
        </form>
    </section>

    <section class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>昵称</th>
                    <th>头像</th>
                    <th>角色</th>
                    <th>邮箱</th>
                    <th>状态</th>
                    <th>登录时间</th>
                    <th>登录 IP</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->username }}</td>
                        <td>{{ $row->nickname ?: '-' }}</td>
                        <td><a href="{{ $row->avatarUrl() }}" target="_blank" class="badge">查看</a></td>
                        <td>{{ $groupMap[$row->id] ?? '-' }}</td>
                        <td>{{ $row->email ?: '-' }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->logintime ? date('Y-m-d H:i:s', (int) $row->logintime) : '-' }}</td>
                        <td>{{ $row->loginip ?: '-' }}</td>
                        <td>
                            @if((int) $row->id !== (int) $selfId)
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a class="badge" href="{{ route('admin.admin-users.edit', $row) }}">编辑</a>
                                    <form method="post" action="{{ route('admin.admin-users.destroy', $row) }}" onsubmit="return confirm('确认删除该管理员吗？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="badge" style="border:0;cursor:pointer;background:#fef2f2;color:#b91c1c;">删除</button>
                                    </form>
                                </div>
                            @else
                                <span class="meta">当前账号</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="meta">暂无管理员数据。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top:16px;">{{ $rows->links() }}</div>
    </section>
@endsection
