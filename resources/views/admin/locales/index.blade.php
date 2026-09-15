@extends('admin.layout')
@section('title', '语言管理')
@section('icon', 'language')
@php
    $composeFocus = session('locale_quick_added') || $locales->isEmpty();
@endphp
@section('content')
    <div class="entry-index locale-index">
        <div class="locale-compose">
            <form method="post" action="{{ route('admin.locales.store') }}">
                @csrf
                <input type="hidden" name="is_active" value="1">
                <label class="locale-compose-label" for="locale-quick-label">新增语言</label>
                <div class="locale-compose-row">
                    <input id="locale-quick-label" type="text" name="label" value="" placeholder="名称，如 日本語" aria-label="语言名称" required @if($composeFocus) autofocus @endif>
                    <input class="locale-code" type="text" name="code" value="" placeholder="代码，如 ja" aria-label="语言代码" required>
                    <button class="btn" type="submit">添加</button>
                </div>
                <p class="muted field-hint">添加后前台就可以切换。需要改默认语言或地址前缀时，<a href="{{ route('admin.locales.create') }}">打开完整表单</a>。</p>
            </form>
        </div>

        @if($locales->isEmpty())
            <div class="list-empty">
                <p>还没有语言。</p>
                <p class="muted">在上方填写名称和代码即可添加。</p>
            </div>
        @else
            <p class="muted recycle-lead">点名称去改。默认语言的页面一般不加前缀，例如中文是 / 而不是 /zh。停用后前台不再提供这种语言，已写好的译文还在。</p>
            <table class="data">
                <thead>
                <tr>
                    <th>语言</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($locales as $locale)
                    <tr @class(['is-off' => ! $locale->is_active])>
                        <td>
                            <div class="entry-row-title-line">
                                <a class="entry-row-title" href="{{ route('admin.locales.edit', $locale) }}">{{ $locale->label ?: $locale->code }}</a>
                                @if($locale->is_default)
                                    <span class="badge">默认</span>
                                @endif
                                @unless($locale->is_active)
                                    <span class="badge badge-off">停用</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">
                                {{ $locale->code }}
                                @if($locale->native && $locale->native !== $locale->label)
                                    · 前台叫 {{ $locale->native }}
                                @endif
                                @if($locale->is_default)
                                    · {{ $locale->hide_default ? '地址不加前缀' : '地址带语言前缀' }}
                                @endif
                            </div>
                        </td>
                        <td class="actions">
                            @unless($locale->is_default)
                                <form action="{{ route('admin.locales.default', $locale) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm btn-muted" type="submit" @disabled(! $locale->is_active)>设为默认</button>
                                </form>
                                <form action="{{ route('admin.locales.toggle', $locale) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm btn-muted" type="submit">{{ $locale->is_active ? '停用' : '启用' }}</button>
                                </form>
                                <form action="{{ route('admin.locales.destroy', $locale) }}" method="post" onsubmit="return confirm(@json('确定删除「'.($locale->label ?: $locale->code).'」？前台不能再切这种语言，已写好的译文还在。'))">
                                    @csrf @method('DELETE')
                                    <button class="btn-quiet" type="submit">删除</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ $locales->count() }} 种</p>
        @endif
    </div>
@endsection
