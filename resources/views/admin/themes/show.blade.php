@extends('admin.layout')
@section('title', $theme['name'])
@section('icon', 'palette')
@php
    $theme = $theme ?? [];
    $models = $models ?? [];
    $selected = $selected ?? [];
    $fields = $fields ?? [];
    $locales = $locales ?? [];
    $phrases = $phrases ?? [];
    $phraseKeys = $phraseKeys ?? [];
    $phraseCatalog = $phraseCatalog ?? [];
    $categories = $categories ?? [];
    $group = null;
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.themes.templates', $theme['slug']) }}">编辑模板</a>
    <a class="btn btn-muted btn-sm" href="{{ route('admin.themes.index') }}">{{ admin_back('themes') }}</a>
@endsection
@section('content')
    <div class="theme-setup">
        <p class="muted recycle-lead">这一页只改「{{ $theme['name'] }}」这一套。多语言开关和模板短句、要补的模型、自定义参数都在这里。要改 Blade 点「编辑模板」。全站共用的搜索/登录文案在「<a href="{{ route('admin.locales.index', ['tab' => 'theme']) }}">语言 → 主题文案</a>」，Logo 和主色在「<a href="{{ route('admin.settings.edit', ['tab' => 'look']) }}">设置 → 外观</a>」。</p>

        <div class="theme-setup-hero html-cache-card">
            <a class="theme-setup-shot" href="{{ $theme['preview_url'] }}" target="_blank" rel="noopener">
                <img src="{{ $theme['preview_url'] }}" alt="{{ $theme['name'] }}预览">
            </a>
            <div>
                <div class="theme-card-head">
                    <h3 style="margin:0">{{ $theme['name'] }}</h3>
                    <div class="theme-card-badges">
                        @if($theme['current'])
                            <span class="badge badge-ok">当前使用</span>
                        @endif
                        @if($theme['builtin'])
                            <span class="badge">自带</span>
                        @endif
                    </div>
                </div>
                <p class="muted" style="margin:8px 0 0">{{ $theme['slug'] }}@if($theme['description'] !== '') · {{ $theme['description'] }}@endif</p>
                <p class="muted field-hint" style="margin:8px 0 0"><code>{{ $theme['path'] }}</code></p>
                <div class="theme-card-actions" style="margin-top:12px">
                    @if($theme['current'])
                        <span class="muted">正在用</span>
                    @else
                        <form action="{{ route('admin.themes.apply', $theme['slug']) }}" method="post">
                            @csrf
                            <button class="btn btn-sm" type="submit" @disabled(! $theme['complete'])>用于本站</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <form method="post" action="{{ route('admin.themes.update', $theme['slug']) }}">
            @csrf
            @method('PUT')

            <div class="html-cache-card">
                <h3>模板多语言</h3>
                <p class="muted field-hint">勾上「多语言」表示这套带语言切换。下面的短句只覆盖这一套；留空则用全站主题文案。</p>
                <label class="inline">
                    <input type="hidden" name="i18n" value="0">
                    <input type="checkbox" name="i18n" value="1" @checked($theme['i18n'])>
                    多语言（语言切换）
                </label>
                <label class="inline">
                    <input type="hidden" name="responsive" value="0">
                    <input type="checkbox" name="responsive" value="1" @checked($theme['responsive'])>
                    自适应（手机宽度）
                </label>
                <div class="table-scroll" style="margin-top:12px">
                    <table class="data">
                        <thead>
                        <tr>
                            <th>短句</th>
                            @foreach($locales as $code => $meta)
                                <th>{{ $meta['label'] ?? $code }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($phraseKeys as $key)
                            @php $meta = $phraseCatalog[$key] ?? ['label' => $key]; @endphp
                            <tr>
                                <td>{{ $meta['label'] ?? $key }}<div class="entry-row-meta"><code>cms_ui('{{ $key }}')</code></div></td>
                                @foreach($locales as $code => $locMeta)
                                    <td>
                                        <input type="text" name="phrases[{{ $key }}][{{ $code }}]" value="{{ old('phrases.'.$key.'.'.$code, $phrases[$key][$code] ?? '') }}" placeholder="{{ $meta[$code] ?? $meta['zh_cn'] ?? '' }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="html-cache-card">
                <h3>这套要用的模型</h3>
                <p class="muted field-hint">勾选后，点「用于本站」或保存（正在用这套时）会补上还没有的模型。已有字段和文章不改。栏目仍按 <code>theme.json</code> 里写的补空栏目。</p>
                <div class="theme-model-grid">
                    @foreach($models as $slug => $name)
                        <label class="inline">
                            <input type="checkbox" name="models[]" value="{{ $slug }}" @checked(in_array($slug, $selected, true))>
                            {{ $name }}
                            @php $row = collect($theme['models'])->firstWhere('slug', $slug); @endphp
                            @if($row)
                                @if(!empty($row['ready']))
                                    <span class="badge badge-ok">已有</span>
                                @else
                                    <span class="badge badge-off">未装</span>
                                @endif
                            @endif
                        </label>
                    @endforeach
                </div>
                @if($categories !== [])
                    <p class="muted field-hint" style="margin-top:12px">会补的空栏目：
                        {{ collect($categories)->map(function ($cat) {
                            $title = $cat['title'] ?? '';
                            if (is_array($title)) {
                                $title = $title['zh_cn'] ?? reset($title);
                            }
                            return $title;
                        })->implode('、') }}
                    </p>
                @endif
            </div>

            <div class="html-cache-card">
                <h3>自定义参数</h3>
                <p class="muted field-hint">写在 <code>theme.json</code> 的 <code>config</code> 里，前台用 <code>cms_theme('键')</code>。每套主题各自保存，换皮不会把另一套的值带过去。若开了磁盘静态页，保存后到「<a href="{{ route('admin.html-cache.edit') }}">缓存</a>」再生成一次。</p>
                @foreach($fields as $field)
                    @if(($field['group'] ?? '') !== $group)
                        @php $group = $field['group'] ?? ''; @endphp
                        @if($group !== '')
                            <h4 class="theme-setup-group">{{ $group }}</h4>
                        @endif
                    @endif
                    @if($field['type'] === 'checkbox')
                        <label class="inline">
                            <input type="hidden" name="vars[{{ $field['key'] }}]" value="0">
                            <input type="checkbox" name="vars[{{ $field['key'] }}]" value="1" @checked(old('vars.'.$field['key'], $field['current']) === '1' || old('vars.'.$field['key'], $field['current']) === 'true')>
                            {{ $field['title'] }}
                        </label>
                    @else
                        <label for="theme-var-{{ $field['key'] }}">{{ $field['title'] }}</label>
                        @if($field['type'] === 'textarea')
                            <textarea id="theme-var-{{ $field['key'] }}" name="vars[{{ $field['key'] }}]" rows="3">{{ old('vars.'.$field['key'], $field['current']) }}</textarea>
                        @elseif($field['type'] === 'select')
                            <select id="theme-var-{{ $field['key'] }}" name="vars[{{ $field['key'] }}]">
                                @foreach($field['options'] as $opt)
                                    <option value="{{ $opt['value'] }}" @selected((string) old('vars.'.$field['key'], $field['current']) === (string) $opt['value'])>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        @elseif($field['type'] === 'color')
                            <input id="theme-var-{{ $field['key'] }}" type="text" name="vars[{{ $field['key'] }}]" value="{{ old('vars.'.$field['key'], $field['current']) }}" placeholder="#1b4f72" maxlength="7">
                        @else
                            <input id="theme-var-{{ $field['key'] }}" type="text" name="vars[{{ $field['key'] }}]" value="{{ old('vars.'.$field['key'], $field['current']) }}">
                        @endif
                    @endif
                    @if(($field['tips'] ?? '') !== '')
                        <p class="muted field-hint">{{ $field['tips'] }} · <code>cms_theme('{{ $field['key'] }}')</code></p>
                    @else
                        <p class="muted field-hint"><code>cms_theme('{{ $field['key'] }}')</code></p>
                    @endif
                @endforeach
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">保存这套配置</button>
                <a class="btn btn-muted" href="{{ route('admin.themes.index') }}">{{ admin_back('themes') }}</a>
            </div>
        </form>
    </div>
@endsection
