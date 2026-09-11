@extends('admin.layout')
@section('title', $redirect->exists ? '编辑重定向' : '新建重定向')
@section('icon', 'exchange-alt')
@section('content')
    <form method="post" action="{{ $redirect->exists ? route('admin.redirects.update', $redirect) : route('admin.redirects.store') }}">
        @csrf
        @if($redirect->exists) @method('PUT') @endif
        <label>源路径（如 /old-page 或 old-page）</label>
        <input type="text" name="source_path" value="{{ old('source_path', $redirect->source_path) }}" required placeholder="/old-news">
        <label>目标（站内路径 /news 或完整 https://…）</label>
        <input type="text" name="target_url" value="{{ old('target_url', $redirect->target_url) }}" required placeholder="/news">
        <label>状态码</label>
        <select name="status_code">
            <option value="301" @selected((string) old('status_code', $redirect->status_code ?? 301) === '301')>301 永久</option>
            <option value="302" @selected((string) old('status_code', $redirect->status_code ?? 301) === '302')>302 临时</option>
        </select>
        <label>备注</label>
        <input type="text" name="note" value="{{ old('note', $redirect->note) }}">
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $redirect->is_active ?? true))> 启用</label>
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.redirects.index') }}">返回</a>
        </p>
    </form>
@endsection
