@extends('admin.layout')
@section('title', admin_t('nav.themes'))
@section('icon', 'palette')
@php
    $themes = $themes ?? [];
    $current = $current ?? '';
    $add = $add ?? '';
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm{{ $add === 'copy' ? ' is-on' : '' }}" href="{{ route('admin.themes.index', ['add' => 'copy']) }}">复制一套</a>
    <a class="btn btn-muted btn-sm{{ $add === 'upload' ? ' is-on' : '' }}" href="{{ route('admin.themes.index', ['add' => 'upload']) }}">上传 zip</a>
@endsection
@section('content')
    <div class="entry-index theme-index">
        <p class="muted recycle-lead">点卡片或「配置」进入这一套：多语言短句、要补的模型和自定义参数。点「用于本站」会补上缺的模型和空栏目（<code>theme.json</code> 里可写中英标题），已有文章和字段不改。自带主题都带语言切换和手机宽度。全站短句在「<a href="{{ route('admin.locales.index', ['tab' => 'theme']) }}">语言 → 主题文案</a>」。也可以把文件夹放到 <code>resources/views/themes/</code> 下。换完后还是旧样子，到「<a href="{{ route('admin.html-cache.edit') }}">缓存</a>」清空。</p>

        @if($add === 'copy')
            @php $from = old('from', 'enterprise'); @endphp
            <div class="theme-add">
                <form method="post" action="{{ route('admin.themes.store') }}" class="theme-add-form">
                    @csrf
                    <div class="theme-add-row">
                        <span class="theme-add-k">复制</span>
                        <select name="from" aria-label="从哪套复制">
                            @foreach($themes as $item)
                                @if($item['complete'])
                                    <option value="{{ $item['slug'] }}" @selected($from === $item['slug'])>{{ $item['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input id="theme-name" type="text" name="name" value="{{ old('name') }}" placeholder="显示名称，如 品牌站" required autofocus>
                        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="标识 brand" aria-label="主题标识" required>
                        <button class="btn" type="submit">复制</button>
                        <a class="btn-quiet" href="{{ route('admin.themes.index') }}">收起</a>
                    </div>
                </form>
            </div>
        @elseif($add === 'upload')
            <div class="theme-add">
                <form method="post" action="{{ route('admin.themes.upload') }}" enctype="multipart/form-data" class="theme-add-form">
                    @csrf
                    <div class="theme-add-row">
                        <span class="theme-add-k">上传</span>
                        <input id="theme-pack" type="file" name="pack" accept=".zip,application/zip" required>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="显示名称，可空" aria-label="上传后的名称">
                        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="标识，可空" aria-label="上传后的标识">
                        <button class="btn" type="submit">上传</button>
                        <a class="btn-quiet" href="{{ route('admin.themes.index') }}">收起</a>
                    </div>
                </form>
            </div>
        @endif

        @if($themes === [])
            <div class="list-empty">
                <p>还没有主题。</p>
            </div>
        @else
            <div class="theme-grid">
                @foreach($themes as $item)
                    <article class="theme-card{{ $item['current'] ? ' is-current' : '' }}{{ $item['complete'] ? '' : ' is-off' }}">
                        <a class="theme-card-shot" href="{{ $item['preview_url'] }}" target="_blank" rel="noopener">
                            <img src="{{ $item['preview_url'] }}" alt="{{ $item['name'] }}预览">
                        </a>
                        <div class="theme-card-body">
                        <div class="theme-card-head">
                            <div class="theme-card-title"><a href="{{ route('admin.themes.show', $item['slug']) }}">{{ $item['name'] }}</a></div>
                            <div class="theme-card-badges">
                                @if($item['current'])
                                    <span class="badge badge-ok">当前使用</span>
                                @endif
                                @if($item['builtin'])
                                    <span class="badge">自带</span>
                                @endif
                                @unless($item['complete'])
                                    <span class="badge badge-off">缺首页</span>
                                @endunless
                                @if(($item['models'] ?? []) !== [] && empty($item['models_ready']))
                                    <span class="badge badge-off">缺模型</span>
                                @endif
                            </div>
                        </div>
                        <p class="theme-card-meta">
                            {{ $item['slug'] }}
                            @if($item['description'] !== '')
                                · {{ $item['description'] }}
                            @endif
                        </p>
                        <p class="theme-card-caps">
                            @if(!empty($item['i18n']))
                                <span>多语言</span>
                            @endif
                            @if(!empty($item['responsive']))
                                <span>自适应</span>
                            @endif
                        </p>
                        @if(($item['models'] ?? []) !== [])
                            <p class="theme-card-models">模型 {{ collect($item['models'])->pluck('name')->implode('、') }}</p>
                        @endif
                        <div class="theme-card-actions">
                            <a class="btn-quiet" href="{{ route('admin.themes.show', $item['slug']) }}">配置</a>
                            @if($item['current'] && ($item['models'] ?? []) !== [] && empty($item['models_ready']))
                                <form action="{{ route('admin.themes.apply', $item['slug']) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm" type="submit">补上模型</button>
                                </form>
                            @elseif($item['current'])
                                <span class="muted">正在用</span>
                            @else
                                <form action="{{ route('admin.themes.apply', $item['slug']) }}" method="post">
                                    @csrf
                                    <button class="btn btn-sm" type="submit" @disabled(! $item['complete'])>用于本站</button>
                                </form>
                            @endif
                            @unless($item['builtin'])
                                <form action="{{ route('admin.themes.destroy', $item['slug']) }}" method="post" onsubmit="return confirm(@json('去掉「'.$item['name'].'」？文件夹会删掉。'))">
                                    @csrf @method('DELETE')
                                    <button class="btn-quiet" type="submit" @disabled($item['current'])>去掉</button>
                                </form>
                            @endunless
                        </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="muted entry-index-count">共 {{ count($themes) }} 套</p>
        @endif
    </div>
@endsection
