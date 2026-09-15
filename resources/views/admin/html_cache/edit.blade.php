@extends('admin.layout')
@section('title', admin_t('nav.cache'))
@section('icon', 'bolt')
@php
    $enabled = (bool) ($enabled ?? false);
    $ttl = (int) ($ttl ?? 3600);
    $ttlOptions = $ttlOptions ?? \App\Services\Cms\HtmlCacheService::ttlOptions();
    $lastBust = $lastBust ?? null;
    $lastBustLabel = $lastBustLabel ?? '还没有刷新过';
    $diskEnabled = (bool) ($diskEnabled ?? false);
    $diskCount = (int) ($diskCount ?? 0);
    $diskJob = $diskJob ?? ['status' => 'idle', 'percent' => 0, 'message' => ''];
@endphp
@section('header_actions')
    <form method="post" action="{{ route('admin.cache.clear') }}" onsubmit="return confirm('会清掉模板、配置和已存页面。第一次打开会稍慢，随后再生成。确定？')">
        @csrf
        <button class="btn btn-muted btn-sm" type="submit">清空缓存</button>
    </form>
@endsection
@section('content')
    <div class="html-cache-page">
        <p class="muted recycle-lead">全页缓存会把首页、栏目和文章先做好存起来，访客打开更快。后台、会员中心、搜索和下载不会存。改内容后会自动换新的。模板和配置缓存用右上角「清空缓存」。</p>

        <div class="html-cache-card">
            <div class="html-cache-status">
                <h3>页面缓存</h3>
                @if($enabled)
                    <span class="badge badge-ok">已开启</span>
                @else
                    <span class="badge badge-off">未开启</span>
                @endif
            </div>
            <form method="post" action="{{ route('admin.html-cache.update') }}">
                @csrf
                @method('PUT')
                <label class="inline">
                    <input type="hidden" name="html_cache_enabled" value="0">
                    <input type="checkbox" name="html_cache_enabled" value="1" @checked($enabled)>
                    启用，访客看到的是刚生成好的页面
                </label>
                <p class="muted field-hint">适合访问多、改动不那么频繁的站点。刚发布的改动最多等你选的时长就会出现。</p>

                <label for="html-cache-ttl">保存多久</label>
                <select id="html-cache-ttl" class="html-cache-ttl" name="html_cache_ttl">
                    @foreach($ttlOptions as $seconds => $label)
                        <option value="{{ $seconds }}" @selected($ttl === (int) $seconds)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="muted field-hint">选「马上换新」时，改完内容前台就会用新页面；选一段时间则期间都用存好的。</p>

                <div class="form-actions">
                    <button class="btn" type="submit">保存设置</button>
                </div>
            </form>
        </div>

        <div class="html-cache-card">
            <h3>现在</h3>
            <p class="muted" style="margin:0 0 8px">
                {{ $lastBustLabel }}
                @if(!empty($lastBust['at']))
                    · {{ \Illuminate\Support\Carbon::parse($lastBust['at'])->format('Y-m-d H:i') }}
                @endif
            </p>
            <div class="html-cache-actions">
                <form method="post" action="{{ route('admin.html-cache.warm') }}">
                    @csrf
                    <input type="hidden" name="entries" value="30">
                    <button class="btn" type="submit" @disabled(! $enabled)>预热常用页</button>
                </form>
                <form method="post" action="{{ route('admin.html-cache.clear') }}" onsubmit="return confirm('清空后访客第一次打开会稍慢，随后又会存起来。确定？')">
                    @csrf
                    <button class="btn btn-muted" type="submit">清空已存页面</button>
                </form>
            </div>
            <p class="muted field-hint">
                @if($enabled)
                    预热会现在生成首页、栏目和最近 30 篇文章，避免刚打开时前台还要现算。
                @else
                    打开并保存后，才能预热。
                @endif
            </p>
        </div>

        <div class="html-cache-card" id="diskHtmlCard">
            <div class="html-cache-status">
                <h3>磁盘静态页</h3>
                @if($diskEnabled)
                    <span class="badge badge-ok">已打开</span>
                @else
                    <span class="badge badge-off">未打开</span>
                @endif
            </div>
            <p class="muted field-hint">把栏目和文章写成文件。生成后前台会直接用这些文件，不必再渲染模板。本机开发也会读。nginx 怎么配见系统「<a href="{{ route('admin.help') }}">说明</a>」。</p>
            <form method="post" action="{{ route('admin.html-cache.disk-update') }}">
                @csrf
                @method('PUT')
                <label class="inline">
                    <input type="hidden" name="disk_html_enabled" value="0">
                    <input type="checkbox" name="disk_html_enabled" value="1" @checked($diskEnabled)>
                    启用，允许在后台生成静态文件
                </label>
                <div class="form-actions">
                    <button class="btn btn-muted" type="submit">保存这项</button>
                </div>
            </form>
            <p id="diskHtmlCount" class="muted">
                @if($diskCount > 0)
                    目前有 {{ $diskCount }} 个文件。
                @else
                    还没有生成过文件。
                @endif
            </p>
            <div class="html-cache-actions">
                <button class="btn" type="button" id="diskBuildBtn" data-off="{{ $diskEnabled ? '0' : '1' }}" @disabled(! $diskEnabled)>开始生成</button>
                <button class="btn-quiet" type="button" id="diskCancelBtn" hidden>停止</button>
                <button class="btn-quiet" type="button" id="diskClearBtn">删掉静态文件</button>
            </div>
            <p class="muted field-hint">
                @if($diskEnabled)
                    会生成首页、栏目和已发布的文章。页面多时会分批写，文件多时会分批删。进行中请不要关掉这个页面。
                @else
                    打开并保存后，才能生成。已有文件仍可分批删除。
                @endif
            </p>
            <div class="disk-html-progress{{ in_array($diskJob['status'] ?? '', ['done', 'cancelled'], true) ? ' is-done' : '' }}"
                 id="diskHtmlProgress"
                 data-status="{{ $diskJob['status'] ?? 'idle' }}"
                 data-kind="{{ $diskJob['kind'] ?? 'build' }}"
                 data-percent="{{ $diskJob['percent'] ?? 0 }}"
                 @if(($diskJob['status'] ?? 'idle') === 'idle') hidden @endif>
                <div class="disk-html-bar"><i id="diskHtmlBar" style="width: {{ (int) ($diskJob['percent'] ?? 0) }}%"></i></div>
                <p class="muted" id="diskHtmlMsg" style="margin:8px 0 0">{{ $diskJob['message'] ?? '' }}</p>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
(function () {
    var btn = document.getElementById('diskBuildBtn');
    var stopBtn = document.getElementById('diskCancelBtn');
    var clearBtn = document.getElementById('diskClearBtn');
    var box = document.getElementById('diskHtmlProgress');
    var bar = document.getElementById('diskHtmlBar');
    var msg = document.getElementById('diskHtmlMsg');
    var count = document.getElementById('diskHtmlCount');
    if (!btn || !box || !bar || !msg) return;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var urls = {
        start: @json(route('admin.html-cache.disk-build')),
        clear: @json(route('admin.html-cache.disk-clear')),
        step: @json(route('admin.html-cache.disk-step')),
        cancel: @json(route('admin.html-cache.disk-cancel'))
    };
    var looping = false;
    var stepChunk = 6;
    function locked() {
        return btn.getAttribute('data-off') === '1';
    }

    function headers() {
        return {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    function idleButtons() {
        btn.disabled = locked();
        if (clearBtn) clearBtn.disabled = false;
        if (stopBtn) stopBtn.hidden = true;
    }

    function paint(data) {
        box.hidden = false;
        bar.style.width = (data.percent || 0) + '%';
        var kind = data.kind || 'build';
        var fallback = kind === 'clear'
            ? ('已删 ' + (data.done || 0) + ' / ' + (data.total || 0))
            : ('已写 ' + (data.done || 0) + ' / ' + (data.total || 0));
        msg.textContent = data.message || fallback;
        box.classList.toggle('is-done', data.status === 'done');
        box.classList.toggle('is-stop', data.status === 'cancelled' || data.status === 'error');
        if (count && typeof data.file_count === 'number') {
            count.textContent = data.file_count > 0
                ? ('目前有 ' + data.file_count + ' 个文件。')
                : '还没有生成过文件。';
        }
        var busy = data.status === 'running';
        btn.disabled = busy || locked();
        if (clearBtn) clearBtn.disabled = busy;
        if (stopBtn) stopBtn.hidden = !busy;
        if (kind === 'clear') stepChunk = 40;
        else if (kind === 'build') stepChunk = 6;
    }

    async function post(url, body) {
        var res = await fetch(url, { method: 'POST', headers: headers(), body: JSON.stringify(body || {}) });
        var data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error(data.message || '请求失败');
        }
        return data;
    }

    async function loop(chunk) {
        if (chunk) stepChunk = chunk;
        looping = true;
        try {
            while (looping) {
                var data = await post(urls.step, { chunk: stepChunk });
                paint(data);
                if (data.status !== 'running') break;
            }
        } catch (e) {
            paint({ status: 'error', percent: 0, message: e.message || '处理中断了', done: 0, total: 0 });
        }
        looping = false;
        idleButtons();
    }

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        if (clearBtn) clearBtn.disabled = true;
        box.hidden = false;
        msg.textContent = '正在列出要写的页面…';
        bar.style.width = '0%';
        box.classList.remove('is-done', 'is-stop');
        try {
            var data = await post(urls.start, { entries: 0 });
            paint(data);
            if (data.status === 'running') await loop(6);
            else idleButtons();
        } catch (e) {
            paint({ status: 'error', percent: 0, message: e.message || '没能开始', done: 0, total: 0 });
            idleButtons();
        }
    });

    clearBtn?.addEventListener('click', async function () {
        if (!confirm('删掉这些文件后，前台改回动态生成。文件多时会分批删。确定？')) return;
        btn.disabled = true;
        clearBtn.disabled = true;
        box.hidden = false;
        msg.textContent = '正在列出要删的文件…';
        bar.style.width = '0%';
        box.classList.remove('is-done', 'is-stop');
        try {
            var data = await post(urls.clear, {});
            paint(data);
            if (data.status === 'running') await loop(40);
            else idleButtons();
        } catch (e) {
            paint({ status: 'error', percent: 0, message: e.message || '没能开始删除', done: 0, total: 0 });
            idleButtons();
        }
    });

    stopBtn?.addEventListener('click', async function () {
        looping = false;
        try {
            paint(await post(urls.cancel, {}));
        } catch (e) {
            msg.textContent = e.message || '没能停止';
        }
        idleButtons();
    });

    if (box.getAttribute('data-status') === 'running') {
        loop((box.getAttribute('data-kind') || 'build') === 'clear' ? 40 : 6);
    } else if ((box.getAttribute('data-status') || 'idle') !== 'idle') {
        stopBtn && (stopBtn.hidden = true);
    }
})();
</script>
@endpush
