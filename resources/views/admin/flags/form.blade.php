@extends('admin.layout')
@section('title', $flag->exists ? '编辑推荐属性' : '新建推荐属性')
@section('icon', 'flag')
@php $usage = (int) ($usage ?? 0); @endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.flags.index') }}">返回列表</a>
@endsection
@section('content')
    @if($flag->exists && $usage > 0)
        <p class="muted recycle-lead">
            有 <a href="{{ route('admin.entries.index', ['flag' => $flag->name]) }}">{{ $usage }} 篇</a> 打了这个标记。改显示名不影响已勾选的稿；改标识码后，旧稿不会自动跟着改。
        </p>
    @endif
    <form method="post" class="flag-form" action="{{ $flag->exists ? route('admin.flags.update', $flag) : route('admin.flags.store') }}">
        @csrf
        @if($flag->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">

        <label for="flag-title">显示名称</label>
        <input id="flag-title" type="text" name="title" value="{{ old('title', $flag->title) }}" placeholder="如 置顶" required autofocus>
        <p class="muted field-hint">写内容页勾选时看到的名字。</p>

        <label for="flag-name">标识码</label>
        <input id="flag-name" type="text" name="name" value="{{ old('name', $flag->name) }}" placeholder="如 top" @required(! $flag->exists)>
        <p class="muted field-hint">英文字母开头，给模板和筛选用。可空时按显示名生成。</p>

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $flag->is_active ?? true))>
            启用，出现在写内容页
        </label>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $flag->exists ? '保存' : '添加' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.flags.index') }}">取消</a>
        </div>
    </form>
@endsection
