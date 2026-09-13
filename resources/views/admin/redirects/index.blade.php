@extends('admin.layout')
@section('title', '重定向')
@section('icon', 'exchange-alt')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.redirects.create') }}">新建规则</a>
@endsection
@section('content')
    <p class="muted">精确匹配路径（不含域名与查询串）。例：源 <code>/old-news</code> → 目标 <code>/news</code> 或完整 URL。</p>
    <table class="data">
        <thead>
        <tr>
            <th>源路径</th>
            <th>目标</th>
            <th>状态码</th>
            <th>命中</th>
            <th>启用</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($redirects as $row)
            <tr>
                <td><code>{{ $row->source_path }}</code></td>
                <td style="max-width:280px;word-break:break-all">{{ $row->target_url }}</td>
                <td>{{ $row->status_code }}</td>
                <td>
                    {{ $row->hits }}
                    @if($row->last_hit_at)
                        <div class="muted">{{ $row->last_hit_at }}</div>
                    @endif
                </td>
                <td>{{ $row->is_active ? '是' : '否' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.redirects.edit', $row) }}">编辑</a>
                    <form action="{{ route('admin.redirects.toggle', $row) }}" method="post">
                        @csrf
                        <button class="btn btn-sm btn-muted" type="submit">{{ $row->is_active ? '停用' : '启用' }}</button>
                    </form>
                    <form action="{{ route('admin.redirects.destroy', $row) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">暂无重定向规则</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $redirects->links() }}
@endsection
