@extends('admin.layout')

@section('content')
    <section class="card">
        <form method="post" action="{{ route('admin.attachments.update', $attachment) }}">
            @csrf
            @method('PUT')

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div>
                    <label>标题</label>
                    <input name="title" value="{{ old('title', $attachment->title) }}" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>状态</label>
                    <select name="status" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="normal" @selected(old('status', $attachment->status) === 'normal')>正常</option>
                        <option value="hidden" @selected(old('status', $attachment->status) === 'hidden')>隐藏</option>
                    </select>
                </div>
                <div style="grid-column:1 / -1;">
                    <label>备注</label>
                    <textarea name="remark" style="width:100%;min-height:100px;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ old('remark', $attachment->remark) }}</textarea>
                </div>
                <div>
                    <label>路径</label>
                    <input value="{{ $attachment->path }}" disabled style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;">
                </div>
                <div>
                    <label>预览</label>
                    <div style="padding-top:10px;">
                        <a href="{{ $attachment->previewUrl() }}" target="_blank" class="badge">打开附件</a>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">保存附件</button>
                <a href="{{ route('admin.attachments.index') }}" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </section>
@endsection
