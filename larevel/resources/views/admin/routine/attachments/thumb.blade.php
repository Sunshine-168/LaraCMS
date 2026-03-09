@extends('admin.layout')

@section('content')
    <section class="card">
        <form method="post" action="{{ route('admin.attachments.thumb') }}">
            @csrf

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div>
                    <label>缩略图开关</label>
                    <select name="thumb_on" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="1" @selected(($sdata['thumb_on'] ?? '0') === '1')>开启</option>
                        <option value="0" @selected(($sdata['thumb_on'] ?? '0') === '0')>关闭</option>
                    </select>
                </div>
                <div>
                    <label>生成方式</label>
                    <select name="thumb_type" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        <option value="1" @selected(($sdata['thumb_type'] ?? '1') === '1')>等比例缩放</option>
                        <option value="2" @selected(($sdata['thumb_type'] ?? '1') === '2')>缩放填充</option>
                        <option value="3" @selected(($sdata['thumb_type'] ?? '1') === '3')>居中裁剪</option>
                        <option value="4" @selected(($sdata['thumb_type'] ?? '1') === '4')>左上角裁剪</option>
                        <option value="5" @selected(($sdata['thumb_type'] ?? '1') === '5')>右下角裁剪</option>
                        <option value="6" @selected(($sdata['thumb_type'] ?? '1') === '6')>固定尺寸缩放</option>
                    </select>
                </div>
                <div>
                    <label>缩略图宽度</label>
                    <input name="thumb_width" type="number" min="1" value="{{ $sdata['thumb_width'] ?? 160 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>缩略图高度</label>
                    <input name="thumb_height" type="number" min="1" value="{{ $sdata['thumb_height'] ?? 120 }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">保存设置</button>
                <a href="{{ route('admin.attachments.index') }}" class="btn btn-secondary">返回附件库</a>
            </div>
        </form>
    </section>
@endsection
