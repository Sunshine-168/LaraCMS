@extends('admin.layout')
@section('title', '站点管理')
@section('icon', 'globe')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.sites.create') }}">新建站点</a>
@endsection
@section('content')
    <p class="muted">前台按访问域名匹配站点；后台用顶栏切换当前编辑站。删除站点会级联删除该站全部内容。</p>
    <table>
        <thead>
        <tr>
            <th>名称</th>
            <th>域名</th>
            <th>主题覆盖</th>
            <th>默认</th>
            <th>状态</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($sites as $site)
            <tr>
                <td>{{ $site->name }}</td>
                <td><code>{{ $site->domain }}</code></td>
                <td>{{ $site->theme ?: '—' }}</td>
                <td>{{ $site->is_default ? '是' : '' }}</td>
                <td>{{ $site->is_active ? '启用' : '停用' }}</td>
                <td>
                    <a href="{{ route('admin.sites.edit', $site) }}">编辑</a>
                    @unless($site->is_default)
                        <form action="{{ route('admin.sites.destroy', $site) }}" method="post" style="display:inline" onsubmit="return confirm('确认删除该站点及其全部内容？')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">删除</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $sites->links() }}
@endsection
