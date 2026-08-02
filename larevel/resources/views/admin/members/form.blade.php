@extends('admin.layout')
@section('title', $member->exists ? '编辑会员' : '新建会员')
@section('content')
    <form method="post" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}">
        @csrf
        @if($member->exists) @method('PUT') @endif
        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $member->name) }}" required>
        <label>邮箱</label>
        <input type="email" name="email" value="{{ old('email', $member->email) }}" required>
        <label>分组</label>
        <select name="group_id">
            <option value="">未分组</option>
            @foreach($groups ?? [] as $g)
                <option value="{{ $g->id }}" @selected(old('group_id', $member->group_id) == $g->id)>{{ $g->name }}</option>
            @endforeach
        </select>
        <label>密码{{ $member->exists ? '（留空不改）' : '' }}</label>
        <input type="password" name="password" {{ $member->exists ? '' : 'required' }}>
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $member->is_active ?? true))> 启用</label>
        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.members.index') }}">返回</a>
        </p>
    </form>
@endsection
