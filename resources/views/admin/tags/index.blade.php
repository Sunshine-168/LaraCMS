@extends('admin.layout')
@section('title', '标签管理')
@section('icon', 'tags')
@php
    $q = $q ?? '';
    $unused = (bool) ($unused ?? false);
    $hasFilters = $q !== '' || $unused;
    $qs = function (array $extra = []) use ($q, $unused) {
        return route('admin.tags.index', array_filter(
            array_merge(['q' => $q, 'unused' => $unused ? '1' : null], $extra),
            fn ($v) => $v !== '' && $v !== null
        ));
    };
    $composeFocus = session('tag_quick_added') || ($tags->isEmpty() && ! $hasFilters);
@endphp
@section('content')
    <div class="entry-index tag-index">
        <div class="tag-compose">
            <form method="post" action="{{ route('admin.tags.store') }}" class="tag-compose-form">
                @csrf
                <input type="hidden" name="keep_q" value="{{ $q }}">
                <input type="hidden" name="keep_unused" value="{{ $unused ? '1' : '' }}">
                <label class="tag-compose-label" for="tag-quick-title">新增标签</label>
                <div class="tag-compose-row">
                    <input id="tag-quick-title" type="text" name="title" value="" placeholder="输入名称，如 PHP" aria-label="新增标签" required @if($composeFocus) autofocus @endif>
                    <button class="btn" type="submit">添加</button>
                </div>
                <p class="muted field-hint">回车可连续添加。需要改网址或英文名时，<a href="{{ route('admin.tags.create') }}">打开完整表单</a>。</p>
            </form>
        </div>

        <form method="get" class="filter-bar tag-find" action="{{ route('admin.tags.index') }}">
            @if($unused)
                <input type="hidden" name="unused" value="1">
            @endif
            <input type="search" name="q" value="{{ $q }}" placeholder="搜索标签名或网址标识" aria-label="搜索标签">
            <button class="btn btn-muted" type="submit">搜索</button>
            <a class="chip {{ ! $unused ? 'active' : '' }}" href="{{ $qs(['unused' => null]) }}">全部</a>
            <a class="chip {{ $unused ? 'active' : '' }}" href="{{ $qs(['unused' => '1']) }}">未使用</a>
            @if($hasFilters)
                <a class="chip" href="{{ route('admin.tags.index') }}">清除筛选</a>
            @endif
        </form>

        @if($tags->isEmpty())
            <div class="list-empty">
                @if($hasFilters)
                    <p>没有符合条件的标签。</p>
                    <a class="btn btn-muted" href="{{ route('admin.tags.index') }}">清除筛选</a>
                @else
                    <p>还没有标签。</p>
                    <p class="muted">在上方输入名称即可添加，用来给内容归类。</p>
                @endif
            </div>
        @else
            <table class="data">
                <thead>
                <tr>
                    <th>标签</th>
                    <th style="width:120px">内容</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tags as $tag)
                    <tr>
                        <td>
                            <a class="entry-row-title" href="{{ route('admin.tags.edit', $tag) }}">{{ $tag->t('title') ?: '无标题' }}</a>
                            <div class="entry-row-meta">/tag/{{ $tag->t('slug') }} · #{{ $tag->id }}</div>
                        </td>
                        <td>
                            @if($tag->entries_count > 0)
                                <a href="{{ route('admin.entries.index', ['tag_id' => $tag->id]) }}">{{ $tag->entries_count }} 篇</a>
                            @else
                                <span class="muted">未使用</span>
                            @endif
                        </td>
                        <td class="actions">
                            <a class="btn btn-sm btn-muted" href="{{ cms_front_url('tag/'.$tag->t('slug')) }}" target="_blank" rel="noopener">前台</a>
                            <a class="btn btn-sm btn-muted" href="{{ route('admin.tags.edit', $tag) }}">编辑</a>
                            <form action="{{ route('admin.tags.destroy', $tag) }}" method="post" onsubmit="return confirm(@json($tag->entries_count > 0 ? '「'.$tag->t('title').'」用在 '.$tag->entries_count.' 篇内容上，删除后只去掉标签，稿件还在。确定？' : '确定删除「'.($tag->t('title') ?: '无标题').'」？'))">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $tags->total() }} 个</p>
            {{ $tags->links() }}
        @endif
    </div>
@endsection
