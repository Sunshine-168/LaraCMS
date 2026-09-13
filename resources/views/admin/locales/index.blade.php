@extends('admin.layout')
@section('title', '语言管理')
@section('icon', 'language')
@section('header_actions')
    <a class="btn btn-sm" href="{{ route('admin.locales.create') }}">新建语言</a>
@endsection
@section('content')
    <table>
        <thead><tr><th>代码</th><th>名称</th><th>本地名</th><th>默认</th><th>隐藏默认前缀</th><th>状态</th><th></th></tr></thead>
        <tbody>
        @foreach($locales as $locale)
            <tr>
                <td>{{ $locale->code }}</td>
                <td>{{ $locale->label }}</td>
                <td>{{ $locale->native }}</td>
                <td>{{ $locale->is_default ? '是' : '' }}</td>
                <td>{{ $locale->hide_default ? '是' : '否' }}</td>
                <td>{{ $locale->is_active ? '启用' : '停用' }}</td>
                <td class="actions">
                    <a class="btn btn-sm btn-muted" href="{{ route('admin.locales.edit', $locale) }}">编辑</a>
                    @unless($locale->is_default)
                        <form action="{{ route('admin.locales.destroy', $locale) }}" method="post" onsubmit="return confirm('确认删除？')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">删除</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
