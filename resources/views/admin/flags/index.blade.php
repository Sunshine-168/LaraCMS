@extends('admin.layout')
@section('title', admin_t('nav.flags'))
@section('icon', 'flag')
@php
    $composeFocus = session('flag_quick_added') || $flags->isEmpty();
@endphp
@section('content')
    <div class="entry-index flag-index">
        <div class="flag-compose">
            <form method="post" action="{{ route('admin.flags.store') }}">
                @csrf
                <label class="flag-compose-label" for="flag-quick-title">新增推荐属性</label>
                <div class="flag-compose-row">
                    <input id="flag-quick-title" type="text" name="title" value="" placeholder="显示名，如 置顶" aria-label="显示名称" required @if($composeFocus) autofocus @endif>
                    <input class="flag-key" type="text" name="name" value="" placeholder="标识码，如 top" aria-label="标识码">
                    <button class="btn" type="submit">添加</button>
                </div>
                <p class="muted field-hint">会出现在写内容页。标识码给模板用，建好后尽量不要改。</p>
            </form>
        </div>

        @if($flags->isEmpty())
            <div class="list-empty">
                <p>还没有推荐属性。</p>
                <p class="muted">在上方填写显示名和标识码即可添加。</p>
            </div>
        @else
            <p class="muted recycle-lead">写内容时勾选这些属性，列表里会显示对应标记。停用后不再出现在勾选里，已打在稿上的还在。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>属性</th>
                    <th style="width:120px">内容</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($flags as $i => $flag)
                    <tr @class(['is-off' => ! $flag->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.flags.edit', $flag) }}">{{ $flag->title ?: '无标题' }}</a>
                                @unless($flag->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">{{ $flag->name }}</div>
                        </td>
                        <td>
                            @if(($flag->entries_count ?? 0) > 0)
                                <a href="{{ route('admin.entries.index', ['flag' => $flag->name]) }}">{{ $flag->entries_count }} 篇</a>
                            @else
                                <span class="muted">未使用</span>
                            @endif
                        </td>
                        <td class="actions">
                            @if($i > 0)
                                <form action="{{ route('admin.flags.move', $flag) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="dir" value="up">
                                    <button class="btn btn-sm btn-muted" type="submit">上移</button>
                                </form>
                            @endif
                            @if($i < $flags->count() - 1)
                                <form action="{{ route('admin.flags.move', $flag) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="dir" value="down">
                                    <button class="btn btn-sm btn-muted" type="submit">下移</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.flags.toggle', $flag) }}" method="post">
                                @csrf
                                <button class="btn btn-sm btn-muted" type="submit">{{ $flag->is_active ? '停用' : '启用' }}</button>
                            </form>
                            <form action="{{ route('admin.flags.destroy', $flag) }}" method="post" onsubmit="return confirm(@json(($flag->entries_count ?? 0) > 0 ? '「'.$flag->title.'」还打在 '.$flag->entries_count.' 篇上。删掉后写内容页不再列出，稿上的标记还在。确定？' : '确定删除「'.($flag->title ?: $flag->name).'」？'))">
                                @csrf @method('DELETE')
                                <button class="btn-quiet" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $flags->count() }} 项</p>
        @endif
    </div>
@endsection
