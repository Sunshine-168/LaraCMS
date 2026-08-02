@extends('admin.layout')

@section('content')
    <section class="card" style="margin-bottom:16px;">
        <form method="get" action="{{ route('admin.attachments.index') }}" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <div>
                <label>标题</label>
                <input name="title" value="{{ request('title') }}" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            </div>
            <div>
                <label>后缀</label>
                <select name="ext" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <option value="">全部</option>
                    @foreach($exts as $ext)
                        <option value="{{ $ext }}" @selected(request('ext') === $ext)>{{ $ext }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn">筛选</button>
            <a href="{{ route('admin.attachments.index') }}" class="btn btn-secondary">重置</a>
            <a href="{{ route('admin.attachments.water') }}" class="btn btn-secondary">水印设置</a>
            <a href="{{ route('admin.attachments.thumb') }}" class="btn btn-secondary">缩略图设置</a>
        </form>
    </section>

    <section class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>上传者</th>
                    <th>标题</th>
                    <th>预览</th>
                    <th>大小</th>
                    <th>后缀</th>
                    <th>路径</th>
                    <th>更新时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->adminUser?->username ?: '-' }}</td>
                        <td>{{ $row->title }}</td>
                        <td>
                            <a href="{{ $row->previewUrl() }}" target="_blank" class="badge">查看</a>
                        </td>
                        <td>{{ $row->sizeText() }}</td>
                        <td>{{ $row->ext }}</td>
                        <td style="max-width:260px;word-break:break-all;">{{ $row->path }}</td>
                        <td>{{ $row->update_time ? date('Y-m-d H:i:s', (int) $row->update_time) : '-' }}</td>
                        <td>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a class="badge" href="{{ route('admin.attachments.edit', $row) }}">编辑</a>
                                <form method="post" action="{{ route('admin.attachments.destroy', $row) }}" onsubmit="return confirm('确认删除该附件吗？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="badge" style="border:0;cursor:pointer;background:#fef2f2;color:#b91c1c;">删除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="meta">暂无附件数据。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top:16px;">
            {{ $rows->links() }}
        </div>
    </section>
@endsection
