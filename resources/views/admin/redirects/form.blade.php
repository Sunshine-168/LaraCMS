@extends('admin.layout')
@section('title', $redirect->exists ? '编辑跳转' : '新建跳转')
@section('icon', 'exchange-alt')
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.redirects.index') }}">{{ admin_back('redirects') }}</a>
@endsection
@section('content')
    @if($redirect->exists && $redirect->hits > 0)
        <p class="muted recycle-lead">
            已经有 {{ $redirect->hits }} 人从这条进来
            @if($redirect->last_hit_at)
                ，最近一次 {{ $redirect->last_hit_at->format('Y-m-d H:i') }}
            @endif
            。改新地址后，之后的访客走新的。
        </p>
    @endif
    <form method="post" class="redirect-form" action="{{ $redirect->exists ? route('admin.redirects.update', $redirect) : route('admin.redirects.store') }}">
        @csrf
        @if($redirect->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">

        <label for="redirect-from">旧地址</label>
        <input id="redirect-from" type="text" name="source_path" value="{{ old('source_path', $redirect->source_path) }}" placeholder="/old-news" required autofocus>
        <p class="muted field-hint">访客打开的地址，不用带域名。填 old-news 或整段链接都可以，保存时会自动整理。</p>

        <label for="redirect-to">新地址</label>
        <input id="redirect-to" type="text" name="target_url" value="{{ old('target_url', $redirect->target_url) }}" placeholder="/news" required>
        <p class="muted field-hint">站内填 /news，站外填完整 https://…。不要和旧地址相同。</p>

        <label for="redirect-kind">怎么跳</label>
        <select id="redirect-kind" name="status_code">
            <option value="301" @selected((string) old('status_code', $redirect->status_code ?? 301) === '301')>永久搬走</option>
            <option value="302" @selected((string) old('status_code', $redirect->status_code ?? 301) === '302')>先临时跳转</option>
        </select>
        <p class="muted field-hint">改栏目、改网址一般选永久；活动页、尚未定稿选临时。以后还可以改回来。</p>

        <label for="redirect-note">备注</label>
        <input id="redirect-note" type="text" name="note" value="{{ old('note', $redirect->note) }}" placeholder="给自己看，前台看不到">

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $redirect->is_active ?? true))>
            启用，访客打开旧地址会跳走
        </label>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $redirect->exists ? '保存' : '添加跳转' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.redirects.index') }}">取消</a>
        </div>
    </form>
@endsection
