@extends('admin.layout')
@section('title', '编辑模板 · '.$theme['name'])
@section('icon', 'code')
@section('fullwidth', true)
@php
    $theme = $theme ?? [];
    $groups = $groups ?? [];
    $writable = (bool) ($writable ?? false);
    $hasFiles = false;
    foreach ($groups as $g) {
        if (! empty($g['files'])) {
            $hasFiles = true;
            break;
        }
    }
@endphp
@push('styles')
    @include('admin.themes._editor-styles')
@endpush
@section('content')
<div class="split-side tpl-index">
    <div class="card card-panel">
        <div class="card-header"><span>{{ $theme['name'] }}</span></div>
        <div class="card-body">
            <p class="muted recycle-lead">
                改这一套的 Blade。绿色是 Blade，橙色是 <code>@@cms</code>。Logo 和主色在「<a href="{{ route('admin.settings.edit', ['tab' => 'look']) }}">设置 → 外观</a>」，短句在「<a href="{{ route('admin.themes.show', $theme['slug']) }}">配置</a>」。
                @if($writable)
                    Ctrl+S 直接保存，Ctrl+F 查找。
                @else
                    自带主题只读，请先<a href="{{ route('admin.themes.index', ['add' => 'copy']) }}">复制一套</a>再改。
                @endif
            </p>
            @if($hasFiles)
                <input type="search" id="tpl-search" class="tpl-search" placeholder="搜页面，如 首页、登录" autocomplete="off" aria-label="搜索模板">
                <div class="file-list" id="tpl-files">
                    @foreach($groups as $group)
                        <div class="tpl-group" data-group>
                            <h3>{{ $group['label'] }}</h3>
                            @foreach($group['files'] as $f)
                                <a href="#" class="tpl-file" data-path="{{ $f['path'] }}" data-label="{{ $f['label'] }}">
                                    {{ $f['label'] }}
                                    <small>{{ $f['path'] }}</small>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <p class="muted" id="tpl-filter-empty" hidden>没有匹配的页面。</p>
            @else
                <div class="list-empty">
                    <p>这个主题还没有可改的页面。</p>
                    <p class="muted">把 Blade 文件放到 <code>resources/views/themes/{{ $theme['slug'] }}</code>。</p>
                </div>
            @endif
        </div>
    </div>
    <div class="card card-panel">
        <div class="card-header">
            <span id="tpl-title">模板</span>
            <div>
                <a class="btn btn-muted btn-sm" href="{{ route('admin.themes.show', $theme['slug']) }}">配置</a>
                <a class="btn btn-muted btn-sm" href="{{ route('admin.themes.index') }}">{{ admin_back('themes') }}</a>
                <button type="button" class="btn btn-muted btn-sm" id="tpl-find" hidden title="Ctrl+F">查找</button>
                <button type="button" class="btn btn-muted btn-sm" id="tpl-attach">插入附件</button>
                <button type="button" class="btn btn-muted btn-sm" id="tpl-rollback" disabled>回滚</button>
                <button type="button" class="btn btn-muted btn-sm" id="tpl-backup" disabled>备份</button>
                <button type="button" class="btn btn-sm" id="tpl-save" disabled>保存</button>
            </div>
        </div>
        <div class="card-body">
            <p class="muted field-hint" id="tpl-meta">
                <span id="tpl-meta-main">从左边点一个页面开始改。保存会先备份，改错了可以回滚。</span>
                <span id="tpl-meta-pos" class="tpl-meta-pos" hidden></span>
            </p>
            <div class="list-empty" id="tpl-empty">
                <p>还没有打开文件。</p>
                <p class="muted">先点「首页」或「整站头尾」。这是 Blade 模板，不是可视化排版。需要图片时点「插入附件」。</p>
            </div>
            <textarea id="tpl-content" class="tpl-editor" hidden spellcheck="false"></textarea>
        </div>
    </div>
</div>
@include('admin.partials.media-picker')
@endsection

@push('scripts')
@include('admin.themes._editor-scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var readUrl = @json(route('admin.themes.templates.read', $theme['slug']));
    var saveUrl = @json(route('admin.themes.templates.save', $theme['slug']));
    var backupUrl = @json(route('admin.themes.templates.backup', $theme['slug']));
    var rollbackUrl = @json(route('admin.themes.templates.rollback', $theme['slug']));
    var writable = @json($writable);
    var current = '';
    var saved = '';
    var backupAt = 0;
    var eol = '\n';
    var applying = false;
    var loadSeq = 0;
    var saving = false;
    var titleEl = document.getElementById('tpl-title');
    var metaMain = document.getElementById('tpl-meta-main');
    var metaPos = document.getElementById('tpl-meta-pos');
    var emptyEl = document.getElementById('tpl-empty');
    var editor = document.getElementById('tpl-content');
    var saveBtn = document.getElementById('tpl-save');
    var backupBtn = document.getElementById('tpl-backup');
    var rollbackBtn = document.getElementById('tpl-rollback');
    var findBtn = document.getElementById('tpl-find');
    var code = window.TplCodeEditor ? window.TplCodeEditor.mount(editor, {
        onSave: function () { saveFile(true); },
        onShow: function () { sizeEditor(); }
    }) : null;
    if (code) code.show(false);

    function sniffEol(text) {
        text = String(text == null ? '' : text);
        if (text.indexOf('\r\n') >= 0) return '\r\n';
        if (text.indexOf('\r') >= 0) return '\r';
        return '\n';
    }
    function normalize(text) {
        return String(text == null ? '' : text).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    }
    function encode(text) {
        text = normalize(text);
        return eol === '\n' ? text : text.replace(/\n/g, eol);
    }
    function getContent() {
        return normalize(code ? code.get() : editor.value);
    }
    function setContent(value) {
        applying = true;
        value = normalize(value);
        if (code) code.set(value);
        else editor.value = value;
        applying = false;
    }
    function dirty() { return current !== '' && getContent() !== saved; }
    function fmtTime(ts) {
        ts = parseInt(ts, 10) || 0;
        if (!ts) return '';
        var d = new Date(ts * 1000);
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }
    function toast(msg, ok) {
        var el = document.querySelector('.flash');
        if (!el) {
            el = document.createElement('div');
            el.className = 'flash';
            var main = document.querySelector('.content');
            if (main) main.insertBefore(el, main.firstChild);
        }
        el.className = ok ? 'flash' : 'flash error';
        el.textContent = msg || '';
    }
    function json(url, opts) {
        opts = opts || {};
        var headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
        };
        if (opts.body) headers['Content-Type'] = 'application/json';
        return fetch(url, {
            method: opts.method || 'GET',
            headers: headers,
            credentials: 'same-origin',
            body: opts.body ? JSON.stringify(opts.body) : undefined
        }).then(function (res) { return res.json(); });
    }
    function cursorBit() {
        if (!code || !current) return '';
        var c = code.cursor();
        return (c.line + 1) + '行 ' + (c.ch + 1) + '列';
    }
    function paintPos() {
        var pos = cursorBit();
        metaPos.hidden = !pos;
        metaPos.textContent = pos;
    }
    function updateChrome() {
        var label = (document.querySelector('.tpl-file.active') || {}).getAttribute('data-label') || current || '模板';
        titleEl.textContent = label + (dirty() ? ' *' : '');
        saveBtn.disabled = !writable || !current || !dirty();
        backupBtn.disabled = !writable || !current;
        rollbackBtn.disabled = !writable || !current || backupAt < 1;
        findBtn.hidden = !current || !code;
    }
    function setMeta(path, bak) {
        backupAt = parseInt(bak, 10) || 0;
        var bits = [path];
        if (backupAt) bits.push('上次备份 ' + fmtTime(backupAt));
        else bits.push('还没有备份');
        metaMain.textContent = bits.join(' · ');
        paintPos();
        updateChrome();
    }
    function sizeEditor() {
        if (code && code.size) code.size();
    }
    function showEditor(on) {
        emptyEl.hidden = on;
        if (code) {
            editor.hidden = true;
            code.show(on);
        } else {
            editor.hidden = !on;
        }
    }
    function markActive(path) {
        document.querySelectorAll('.tpl-file').forEach(function (x) {
            x.classList.toggle('active', x.getAttribute('data-path') === path);
        });
    }
    function openFile(a) {
        var path = a.getAttribute('data-path');
        var label = a.getAttribute('data-label') || path;
        if (path === current) {
            if (code) code.focus();
            else editor.focus();
            return;
        }
        if (current && dirty() && !confirm('当前文件还没保存，换过去会丢掉改动。确定？')) return;
        var seq = ++loadSeq;
        json(readUrl + (readUrl.indexOf('?') >= 0 ? '&' : '?') + 'path=' + encodeURIComponent(path)).then(function (res) {
            if (seq !== loadSeq) return;
            if (!res || !res.ok) {
                toast((res && res.msg) || '读取失败', false);
                return;
            }
            var raw = (res.data && res.data.content) || '';
            eol = sniffEol(raw);
            current = path;
            saved = normalize(raw);
            try {
                setContent(saved);
                showEditor(true);
            } catch (err) {
                editor.hidden = false;
                editor.value = saved;
                toast('高亮没加上，已用文本框打开', false);
            }
            markActive(path);
            setMeta(path, res.data && res.data.backup_at);
            var url = new URL(window.location.href);
            url.searchParams.set('path', path);
            history.replaceState(null, '', url);
            titleEl.textContent = (res.data && res.data.label) || label;
            updateChrome();
        }).catch(function () {
            if (seq !== loadSeq) return;
            toast('读取失败', false);
        });
    }
    document.querySelectorAll('.tpl-file').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            openFile(a);
        });
    });
    function onEditorInput() { if (!applying) updateChrome(); }
    if (code) {
        code.onChange(onEditorInput);
        code.onCursor(paintPos);
    } else {
        editor.addEventListener('input', onEditorInput);
    }
    window.addEventListener('beforeunload', function (e) {
        if (!dirty()) return;
        e.preventDefault();
        e.returnValue = '';
    });
    window.addEventListener('resize', function () {
        if (current) sizeEditor();
    });
    document.addEventListener('keydown', function (e) {
        if (!(e.ctrlKey || e.metaKey) || (e.key !== 's' && e.key !== 'S')) return;
        e.preventDefault();
        saveFile(true);
    });

    var search = document.getElementById('tpl-search');
    var filterEmpty = document.getElementById('tpl-filter-empty');
    function filterFiles() {
        if (!search) return;
        var q = (search.value || '').trim().toLowerCase();
        var hit = false;
        document.querySelectorAll('[data-group]').forEach(function (box) {
            var any = false;
            box.querySelectorAll('.tpl-file').forEach(function (a) {
                var hay = ((a.getAttribute('data-label') || '') + ' ' + (a.getAttribute('data-path') || '')).toLowerCase();
                var on = !q || hay.indexOf(q) >= 0;
                a.hidden = !on;
                if (on) any = true;
            });
            box.hidden = !any;
            if (any) hit = true;
        });
        if (filterEmpty) filterEmpty.hidden = !q || hit;
    }
    if (search) {
        search.addEventListener('input', filterFiles);
        search.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !search.value) return;
            search.value = '';
            filterFiles();
            e.preventDefault();
        });
    }

    function saveFile(quiet) {
        if (!writable) {
            toast('自带主题请先复制一套再改', false);
            return;
        }
        if (!current) {
            if (!quiet) toast('请先选一个页面', false);
            return;
        }
        if (!dirty() || saving) return;
        if (!quiet && !confirm('保存会覆盖这个页面，并先做一份备份。确定？')) return;
        saving = true;
        var payload = encode(getContent());
        var snap = normalize(payload);
        json(saveUrl, { method: 'POST', body: { path: current, content: payload } }).then(function (res) {
            if (res && res.ok) {
                saved = snap;
                setMeta(current, res.data && res.data.backup_at);
            }
            toast((res && res.msg) || '完成', !!(res && res.ok));
        }).catch(function () {
            toast('保存失败', false);
        }).then(function () { saving = false; });
    }
    document.getElementById('tpl-save').addEventListener('click', function () { saveFile(false); });
    document.getElementById('tpl-find').addEventListener('click', function () {
        if (code && code.find) code.find();
    });
    function snippet(url) {
        url = String(url || '').trim();
        if (!url) return '';
        return '<img src="' + url + '" alt="">';
    }
    function copyText(text) {
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).catch(function () {});
            return;
        }
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
    }
    function insertAtCursor(text) {
        if (!text) return;
        if (!current) {
            copyText(text);
            toast('已复制，打开页面后可粘贴到光标处', true);
            return;
        }
        if (code) {
            code.insert(text);
            updateChrome();
            toast('已插入', true);
            return;
        }
        editor.focus();
        var start = editor.selectionStart;
        var end = editor.selectionEnd;
        var val = editor.value;
        editor.value = val.slice(0, start) + text + val.slice(end);
        editor.selectionStart = editor.selectionEnd = start + text.length;
        editor.dispatchEvent(new Event('input'));
        toast('已插入', true);
    }
    function bindDrop(el, onFiles) {
        if (!el) return;
        el.addEventListener('dragover', function (e) {
            e.preventDefault();
            el.classList.add('is-drag');
        });
        el.addEventListener('dragleave', function () { el.classList.remove('is-drag'); });
        el.addEventListener('drop', function (e) {
            e.preventDefault();
            el.classList.remove('is-drag');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                onFiles(e.dataTransfer.files);
            }
        });
    }
    function uploadFiles(files) {
        if (!window.CmsEditor) {
            toast('媒体选择器没加载', false);
            return;
        }
        var list = Array.prototype.filter.call(files || [], function (f) {
            return f && (f.type || '').indexOf('image/') === 0;
        });
        if (!list.length) {
            toast('请拖入 jpg / png / gif / webp 图片', false);
            return;
        }
        var chain = Promise.resolve();
        list.forEach(function (file) {
            chain = chain.then(function () {
                return window.CmsEditor.uploadFile(file).then(function (item) {
                    insertAtCursor(snippet(item && item.url));
                });
            });
        });
        chain.catch(function (err) {
            toast((err && err.message) || '上传失败', false);
        });
    }
    document.getElementById('tpl-attach').addEventListener('click', function () {
        if (!window.CmsEditor) {
            toast('媒体选择器没加载', false);
            return;
        }
        window.CmsEditor.openPicker(function (url) {
            insertAtCursor(snippet(url));
        });
    });
    bindDrop(code ? code.wrap : editor, uploadFiles);
    document.getElementById('tpl-backup').addEventListener('click', function () {
        if (!writable || !current) { toast('请先选一个页面', false); return; }
        json(backupUrl, { method: 'POST', body: { path: current } }).then(function (res) {
            if (res && res.ok) setMeta(current, res.data && res.data.backup_at);
            toast((res && res.msg) || '完成', !!(res && res.ok));
        });
    });
    document.getElementById('tpl-rollback').addEventListener('click', function () {
        if (!writable || !current) { toast('请先选一个页面', false); return; }
        if (!confirm('回到最近一次备份？现在编辑器里没保存的改动会丢掉。')) return;
        json(rollbackUrl, { method: 'POST', body: { path: current } }).then(function (res) {
            if (res && res.ok) {
                json(readUrl + (readUrl.indexOf('?') >= 0 ? '&' : '?') + 'path=' + encodeURIComponent(current)).then(function (r) {
                    if (r && r.ok) {
                        saved = normalize((r.data && r.data.content) || '');
                        setContent(saved);
                        setMeta(current, r.data && r.data.backup_at);
                    }
                });
            }
            toast((res && res.msg) || '完成', !!(res && res.ok));
        });
    });

    var boot = new URL(window.location.href).searchParams.get('path');
    if (boot) {
        var link = document.querySelector('.tpl-file[data-path="' + boot.replace(/"/g, '') + '"]');
        if (link) openFile(link);
    }
})();
</script>
@endpush
