@extends('admin.layout')

@section('content')
    <section class="card">
        <form method="post" action="{{ $admin ? route('admin.admin-users.update', $admin) : route('admin.admin-users.store') }}">
            @csrf
            @if($admin)
                @method('PUT')
            @endif

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div>
                    <label>所属角色</label>
                    <select name="group_id[]" multiple style="width:100%;min-height:140px;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        @foreach($groups as $group)
                            <option value="{{ $group['id'] }}" @selected(in_array((int) $group['id'], array_map('intval', $selectedGroupIds), true))>
                                {{ str_repeat(' - ', $group['parent_id'] ? 1 : 0) }}{{ $group['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>用户名</label>
                    <input name="username" value="{{ old('username', $admin?->username) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>昵称</label>
                    <input name="nickname" value="{{ old('nickname', $admin?->nickname) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>邮箱</label>
                    <input name="email" value="{{ old('email', $admin?->email) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>头像</label>
                    <div style="display:flex;gap:8px;">
                        <input id="admin-avatar-field" name="avatar" value="{{ old('avatar', $admin?->avatar) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <button type="button" class="btn btn-secondary" onclick="openAdminAvatarSelector()">选择</button>
                    </div>
                </div>
                <div>
                    <label>{{ $admin ? '新密码' : '密码' }}</label>
                    <input name="password" type="password" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label>备注</label>
                    <textarea name="remark" rows="4" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ old('remark', $admin?->remark) }}</textarea>
                </div>
                <div>
                    <label>状态</label>
                    <select name="status" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="normal" @selected(old('status', $admin?->status ?? 'normal') === 'normal')>正常</option>
                        <option value="hidden" @selected(old('status', $admin?->status) === 'hidden')>隐藏</option>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">{{ $admin ? '保存管理员' : '创建管理员' }}</button>
                <a href="{{ route('admin.admin-users.index') }}" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </section>

    <script>
        function openAdminAvatarSelector() {
            const win = window.open(@json(route('admin.attachments.select', ['multiple' => 'false', 'mimetype' => 'image/'])), '_blank', 'width=1100,height=760');
            window.addEventListener('message', function handler(event) {
                if (!event.data || event.data.type !== 'attachment:selected') return;
                document.getElementById('admin-avatar-field').value = (event.data.paths || [])[0] || '';
                window.removeEventListener('message', handler);
                if (win && !win.closed) win.close();
            });
        }
    </script>
@endsection
