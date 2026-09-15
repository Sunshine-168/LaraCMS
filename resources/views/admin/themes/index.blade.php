@extends('admin.layout')
@section('title', '外观主题')
@section('icon', 'palette')
@php
    $themes = $themes ?? [];
    $current = $current ?? '';
    $composeFocus = session('theme_quick_added');
@endphp
@section('header_actions')
    <form method="post" action="{{ route('admin.cache.clear') }}" onsubmit="return confirm('清空后第一次打开会稍慢，随后会重新生成。确定？')">
        @csrf
        <button class="btn btn-muted btn-sm" type="submit">清空缓存</button>
    </form>
@endsection
@section('content')
    <div class="entry-index theme-index">
        <p class="muted recycle-lead">前台长什么样由主题决定。可以复制一套自己改，也可以把文件夹放到 <code>resources/views/themes/</code> 下，刷新本页就会出现。换完后还是旧样子，点右上角清空缓存。</p>

        <div class="locale-compose">
            <form method="post" action="{{ route('admin.themes.store') }}">
                @csrf
                <label class="locale-compose-label" for="theme-name">复制一套自己改</label>
                <div class="locale-compose-row theme-compose-row">
                    <select name="from" aria-label="从哪套复制">
                        @foreach($themes as $item)
                            @if($item['complete'])
                                <option value="{{ $item['slug'] }}" @selected($item['slug'] === 'enterprise')>{{ $item['name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input id="theme-name" type="text" name="name" value="" placeholder="显示名称，如 品牌站" required @if($composeFocus) autofocus @endif>
                    <input type="text" name="slug" value="" placeholder="标识 brand" aria-label="主题标识" required>
                    <button class="btn" type="submit">复制</button>
                </div>
                <p class="muted field-hint">标识用小写英文。复制后只改新文件夹，原来的主题不受影响。</p>
            </form>
        </div>

        @if($themes === [])
            <div class="list-empty">
                <p>还没有主题。</p>
            </div>
        @else
            <table class="data">
                <thead>
                <tr>
                    <th>主题</th>
                    <th class="col-actions">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($themes as $item)
                    <tr>
                        <td>
                            <div class="entry-row-title-line">
                                <span class="entry-row-title">{{ $item['name'] }}</span>
                                @if($item['current'])
                                    <span class="badge badge-ok">当前使用</span>
                                @endif
                                @if($item['builtin'])
                                    <span class="badge">自带</span>
                                @endif
                                @unless($item['complete'])
                                    <span class="badge badge-off">缺首页</span>
                                @endunless
                            </div>
                            <div class="entry-row-meta">
                                {{ $item['slug'] }}
                                @if($item['description'] !== '')
                                    · {{ $item['description'] }}
                                @endif
                                · {{ $item['path'] }}
                            </div>
                        </td>
                        <td class="actions">
                            @unless($item['current'])
                                <form action="{{ route('admin.themes.apply', $item['slug']) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm" type="submit" @disabled(! $item['complete'])>用于本站</button>
                                </form>
                            @endunless
                            @unless($item['builtin'])
                                <form action="{{ route('admin.themes.destroy', $item['slug']) }}" method="post" onsubmit="return confirm(@json('去掉「'.$item['name'].'」？文件夹会删掉。'))">
                                    @csrf @method('DELETE')
                                    <button class="btn-quiet" type="submit" @disabled($item['current'])>去掉</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="muted entry-index-count">共 {{ count($themes) }} 套</p>
        @endif
    </div>
@endsection
