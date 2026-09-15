@extends('admin.layout')
@section('title', $collector->exists ? '编辑采集源' : '新建采集源')
@section('icon', 'cloud-download-alt')
@php
    $opt = $collector->options ?? [];
    $type = old('type', $collector->type ?: 'html');
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.collectors.index') }}">返回列表</a>
@endsection
@section('content')
    <form method="post" class="collector-form" id="collectorForm" action="{{ $collector->exists ? route('admin.collectors.update', $collector) : route('admin.collectors.store') }}">
        @csrf
        @if($collector->exists) @method('PUT') @endif

        <label for="collector-name">名称</label>
        <input id="collector-name" type="text" name="name" value="{{ old('name', $collector->name) }}" placeholder="如 官网新闻" required autofocus>
        <p class="muted field-hint">后台列表里看到的名字。</p>

        <label>采集方式</label>
        <div class="collector-types" id="collectorTypes">
            @foreach([
                'html' => ['网站页面', '从列表页抓标题，可翻页、进详情取正文'],
                'rss' => ['RSS 订阅', '填公开的 RSS / Atom 地址即可'],
                'json' => ['JSON 接口', '按字段名从接口列表里取标题和链接'],
            ] as $value => $meta)
                <label class="collector-type{{ $type === $value ? ' is-on' : '' }}">
                    <input type="radio" name="type" value="{{ $value }}" @checked($type === $value)>
                    <strong>{{ $meta[0] }}</strong>
                    <span>{{ $meta[1] }}</span>
                </label>
            @endforeach
        </div>

        <label for="collector-url" id="sourceUrlLabel">列表页地址</label>
        <input id="collector-url" type="url" name="source_url" value="{{ old('source_url', $collector->source_url) }}" placeholder="https://" required>
        <p class="muted field-hint" id="sourceUrlHint">请只采集你有权使用的公开页面。详情页只跟同网站的链接。</p>

        <label for="collector-model">写入模型</label>
        <select id="collector-model" name="model_id" required>
            @foreach($models as $model)
                <option value="{{ $model->id }}" @selected(old('model_id', $collector->model_id) == $model->id)>{{ $model->name }}</option>
            @endforeach
        </select>

        <label for="collector-cat">写入栏目</label>
        <select id="collector-cat" name="category_id">
            <option value="">不指定栏目</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $collector->category_id) == $category->id)>{{ $category->t('title') }}</option>
            @endforeach
        </select>
        <p class="muted field-hint">采进来的稿会进这个栏目，可在内容管理里再改。</p>

        <div class="collector-inline">
            <div>
                <label for="collector-interval">间隔（分钟）</label>
                <input id="collector-interval" type="number" name="interval_minutes" value="{{ old('interval_minutes', $collector->interval_minutes ?: 60) }}" min="1">
            </div>
            <div>
                <label for="collector-limit">每次最多条数</label>
                <input id="collector-limit" type="number" name="limit_items" value="{{ old('limit_items', $collector->limit_items ?: 10) }}" min="1" max="100">
            </div>
        </div>

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $collector->is_active ?? true))>
            启用，到点自动采集
        </label>
        <label class="inline">
            <input type="hidden" name="publish_immediately" value="0">
            <input type="checkbox" name="publish_immediately" value="1" @checked(old('publish_immediately', $collector->publish_immediately ?? false))>
            采集后直接发布（建议先当草稿，核对后再发）
        </label>

        <div id="htmlOpts" class="collector-box" style="{{ $type === 'html' ? '' : 'display:none' }}">
            <h3>网页怎么取</h3>
            <p class="muted field-hint">在浏览器里右键「检查」，把列表里每一条的标签填进来。常见写法：<code>article</code>、<code>li.news</code>、<code>h2 a</code>。</p>
            <label for="item_selector">列表条目</label>
            <input id="item_selector" type="text" name="item_selector" value="{{ old('item_selector', $opt['item_selector'] ?? '') }}" placeholder="article.post">
            <p class="muted field-hint">圈出「一条新闻」的那块，不是整页。</p>

            <label for="link_selector">标题链接</label>
            <input id="link_selector" type="text" name="link_selector" value="{{ old('link_selector', $opt['link_selector'] ?? 'a') }}" placeholder="h2 a">
            <p class="muted field-hint">相对上面这一条。默认取第一条链接。</p>

            <label for="title_selector">标题（可空）</label>
            <input id="title_selector" type="text" name="title_selector" value="{{ old('title_selector', $opt['title_selector'] ?? '') }}" placeholder="h2 a">

            <label for="summary_selector">摘要（可空）</label>
            <input id="summary_selector" type="text" name="summary_selector" value="{{ old('summary_selector', $opt['summary_selector'] ?? '') }}" placeholder=".excerpt">

            <label for="cover_selector">封面图（可空）</label>
            <input id="cover_selector" type="text" name="cover_selector" value="{{ old('cover_selector', $opt['cover_selector'] ?? '') }}" placeholder="img">

            <label for="detail_content_selector">详情正文（可空）</label>
            <input id="detail_content_selector" type="text" name="detail_content_selector" value="{{ old('detail_content_selector', $opt['detail_content_selector'] ?? '') }}" placeholder="article.body">
            <p class="muted field-hint">填写后会再打开每条链接取正文，只跟同网站，失败时用摘要。</p>

            <h3>翻页</h3>
            <label for="page_count">最多翻几页</label>
            <input id="page_count" type="number" name="page_count" value="{{ old('page_count', $opt['page_count'] ?? 1) }}" min="1" max="10">
            <p class="muted field-hint">1 表示只抓当前列表页。最多 10 页，避免把整站爬下来。</p>

            <label for="page_url">翻页地址（可空）</label>
            <input id="page_url" type="text" name="page_url" value="{{ old('page_url', $opt['page_url'] ?? '') }}" placeholder="https://example.com/news?page={page}">
            <p class="muted field-hint">用 <code>{page}</code> 代表页码。留空则在列表地址后加 <code>page=2</code>、<code>page=3</code>。必须与列表页同网站。</p>

            <label for="next_selector">下一页按钮（可空）</label>
            <input id="next_selector" type="text" name="next_selector" value="{{ old('next_selector', $opt['next_selector'] ?? '') }}" placeholder="a.next">
            <p class="muted field-hint">填写后按「下一页」链接走，不再用上面的页码地址。同样只跟同网站。</p>
        </div>

        <div id="jsonOpts" class="collector-box" style="{{ $type === 'json' ? '' : 'display:none' }}">
            <h3>接口字段</h3>
            <label>列表路径</label>
            <input type="text" name="list_path" value="{{ old('list_path', $opt['list_path'] ?? 'items') }}">
            <p class="muted field-hint">数据在哪一层。根数组填 <code>.</code>。</p>
            <label>标题字段</label>
            <input type="text" name="title_key" value="{{ old('title_key', $opt['title_key'] ?? 'title') }}">
            <label>链接字段</label>
            <input type="text" name="link_key" value="{{ old('link_key', $opt['link_key'] ?? 'url') }}">
            <label>摘要字段</label>
            <input type="text" name="summary_key" value="{{ old('summary_key', $opt['summary_key'] ?? 'summary') }}">
            <label>正文字段</label>
            <input type="text" name="content_key" value="{{ old('content_key', $opt['content_key'] ?? 'content') }}">
            <label>去重字段</label>
            <input type="text" name="guid_key" value="{{ old('guid_key', $opt['guid_key'] ?? 'id') }}">
            <label>时间字段</label>
            <input type="text" name="published_key" value="{{ old('published_key', $opt['published_key'] ?? 'published_at') }}">
            <label>封面字段</label>
            <input type="text" name="cover_key" value="{{ old('cover_key', $opt['cover_key'] ?? 'cover') }}">
        </div>

        <div id="collectorPreview" class="collector-preview" hidden>
            <p class="collector-preview-msg muted"></p>
            <ol class="collector-preview-list"></ol>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $collector->exists ? '保存' : '添加采集源' }}</button>
            <button class="btn btn-muted" type="button" id="collectorPreviewBtn">试抓</button>
            <a class="btn btn-muted" href="{{ route('admin.collectors.index') }}">取消</a>
        </div>
    </form>

    @if(!empty($logs) && $logs->isNotEmpty())
        <h3 style="margin-top:28px">最近几次</h3>
        <table class="data">
            <thead><tr><th>时间</th><th>结果</th><th>说明</th></tr></thead>
            <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('m-d H:i') }}</td>
                    <td>
                        @if($log->status === 'ok')
                            <span class="badge badge-ok">成功</span>
                        @elseif($log->status === 'fail')
                            <span class="badge badge-warn">失败</span>
                        @else
                            <span class="badge">{{ $log->status }}</span>
                        @endif
                    </td>
                    <td class="muted">{{ $log->message }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <script>
    (function () {
        const form = document.getElementById('collectorForm');
        const types = document.getElementById('collectorTypes');
        const jsonOpts = document.getElementById('jsonOpts');
        const htmlOpts = document.getElementById('htmlOpts');
        const urlLabel = document.getElementById('sourceUrlLabel');
        const urlHint = document.getElementById('sourceUrlHint');
        const previewBox = document.getElementById('collectorPreview');
        const previewMsg = previewBox?.querySelector('.collector-preview-msg');
        const previewList = previewBox?.querySelector('.collector-preview-list');
        const previewBtn = document.getElementById('collectorPreviewBtn');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const labels = {
            html: ['列表页地址', '请只采集你有权使用的公开页面。详情页只跟同网站的链接。'],
            rss: ['订阅地址', '公开的 RSS / Atom 地址，例如 https://example.com/feed.xml'],
            json: ['接口地址', '返回列表的 JSON 地址。按下面的字段名取标题和链接。'],
        };
        const selectedType = () => form?.querySelector('input[name="type"]:checked')?.value || 'html';
        const apply = () => {
            const t = selectedType();
            jsonOpts.style.display = t === 'json' ? '' : 'none';
            htmlOpts.style.display = t === 'html' ? '' : 'none';
            types.querySelectorAll('.collector-type').forEach((el) => {
                el.classList.toggle('is-on', el.querySelector('input')?.value === t);
            });
            if (urlLabel && labels[t]) urlLabel.textContent = labels[t][0];
            if (urlHint && labels[t]) urlHint.textContent = labels[t][1];
        };
        types?.addEventListener('change', apply);
        apply();

        previewBtn?.addEventListener('click', async () => {
            if (!form || !previewBox) return;
            previewBtn.disabled = true;
            previewBox.hidden = false;
            previewMsg.textContent = '正在抓取…';
            previewList.innerHTML = '';
            const body = new FormData(form);
            body.delete('_method');
            try {
                const res = await fetch(@json(route('admin.collectors.preview')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await res.json();
                previewMsg.textContent = data.msg || (data.message || '试抓失败');
                const items = data.data?.items || [];
                items.forEach((item) => {
                    const li = document.createElement('li');
                    const title = document.createElement(item.link ? 'a' : 'span');
                    title.textContent = item.title || '无标题';
                    if (item.link) {
                        title.href = item.link;
                        title.target = '_blank';
                        title.rel = 'noopener';
                    }
                    li.appendChild(title);
                    if (item.summary) {
                        const s = document.createElement('div');
                        s.className = 'muted';
                        s.textContent = item.summary;
                        li.appendChild(s);
                    }
                    previewList.appendChild(li);
                });
            } catch (e) {
                previewMsg.textContent = '试抓失败，请检查地址和选择器。';
            }
            previewBtn.disabled = false;
        });
    })();
    </script>
@endsection
