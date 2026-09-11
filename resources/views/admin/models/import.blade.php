@extends('admin.layout')
@section('title', '导入内容模型')
@section('icon', 'file-import')
@section('content')
    <form method="post" action="{{ route('admin.models.import.submit') }}" enctype="multipart/form-data">
        @csrf
        <p class="muted">上传由「导出」生成的 JSON。同标识默认覆盖字段定义，不会删除已有文档。</p>
        <label>JSON 文件</label>
        <input type="file" name="file" accept=".json,application/json" required>
        <label class="inline"><input type="checkbox" name="overwrite" value="1" checked> 若 slug 已存在则覆盖字段</label>
        <p style="margin-top:16px">
            <button class="btn" type="submit">导入</button>
            <a class="btn btn-muted" href="{{ route('admin.models.index') }}">返回</a>
        </p>
    </form>
@endsection
