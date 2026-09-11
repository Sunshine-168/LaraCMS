@extends('admin.layout')
@section('title', $flag->exists ? '编辑 Flag' : '新建 Flag')
@section('content')
    <form method="post" action="{{ $flag->exists ? route('admin.flags.update', $flag) : route('admin.flags.store') }}">
        @csrf
        @if($flag->exists) @method('PUT') @endif
        <label>标识 name（英文，如 top）</label>
        <input type="text" name="name" value="{{ old('name', $flag->name) }}" required>
        <label>标题</label>
        <input type="text" name="title" value="{{ old('title', $flag->title) }}" required>
        <label>排序</label>
        <input type="number" name="sort" value="{{ old('sort', $flag->sort ?? 0) }}">
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $flag->is_active ?? true))> 启用</label>
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.flags.index') }}">返回</a>
        </p>
    </form>
@endsection
