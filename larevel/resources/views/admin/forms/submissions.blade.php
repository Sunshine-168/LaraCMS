@extends('admin.layout')
@section('title', '留言收件箱')
@section('icon', 'inbox')
@section('content')
    <div class="toolbar">
        <a class="btn btn-muted" href="{{ route('admin.forms.index') }}"><i class="fas fa-wpforms"></i> 表单定义</a>
        <a class="btn btn-muted" href="{{ route('admin.form-submissions.export', request()->only('form_id')) }}"><i class="fas fa-file-csv"></i> 导出 CSV</a>
        <form method="get" style="display:flex;gap:8px;align-items:center;margin:0">
            <select name="form_id" onchange="this.form.submit()" style="max-width:220px">
                <option value="">全部表单</option>
                @foreach($forms as $f)
                    <option value="{{ $f->id }}" @selected((string)$formId === (string)$f->id)>{{ $f->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <table class="data">
        <thead><tr><th>ID</th><th>表单</th><th>内容</th><th>IP</th><th>时间</th><th>操作</th></tr></thead>
        <tbody>
        @forelse($submissions as $row)
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->form?->name }}</td>
                <td>
                    <table class="data" style="margin:0;border:0">
                        @foreach(($row->data ?? []) as $k => $v)
                            <tr>
                                <th style="width:90px;background:transparent">{{ $k }}</th>
                                <td style="border:0">{{ is_array($v) ? implode(', ', $v) : $v }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
                <td>{{ $row->ip }}</td>
                <td>{{ $row->created_at }}</td>
                <td>
                    <form action="{{ route('admin.form-submissions.destroy', $row) }}" method="post" onsubmit="return confirm('确认删除？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">暂无留言</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $submissions->links() }}
@endsection
