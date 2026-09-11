@extends('admin.layout')
@section('title', $collector->exists ? '编辑采集器' : '新建采集器')
@section('content')
    <form method="post" action="{{ $collector->exists ? route('admin.collectors.update', $collector) : route('admin.collectors.store') }}">
        @csrf
        @if($collector->exists) @method('PUT') @endif

        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $collector->name) }}" required>

        <label>类型</label>
        <select name="type" id="collectorType">
            <option value="rss" @selected(old('type', $collector->type) === 'rss')>RSS / Atom</option>
            <option value="json" @selected(old('type', $collector->type) === 'json')>JSON 列表</option>
            <option value="html" @selected(old('type', $collector->type) === 'html')>HTML 选择器</option>
        </select>

        <label>源地址 URL（请仅采集你有权使用的公开源；HTML 类型只抓该列表页及同源详情，不翻页）</label>
        <input type="url" name="source_url" value="{{ old('source_url', $collector->source_url) }}" required>

        <label>写入模型</label>
        <select name="model_id" required>
            @foreach($models as $model)
                <option value="{{ $model->id }}" @selected(old('model_id', $collector->model_id) == $model->id)>{{ $model->name }}</option>
            @endforeach
        </select>

        <label>写入栏目</label>
        <select name="category_id">
            <option value="">—</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $collector->category_id) == $category->id)>{{ $category->t('title') }}</option>
            @endforeach
        </select>

        <label>间隔（分钟）</label>
        <input type="number" name="interval_minutes" value="{{ old('interval_minutes', $collector->interval_minutes ?: 60) }}" min="1">
        <label>每次最多条数</label>
        <input type="number" name="limit_items" value="{{ old('limit_items', $collector->limit_items ?: 10) }}" min="1" max="100">

        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $collector->is_active ?? true))> 启用</label>
        <label><input type="checkbox" name="publish_immediately" value="1" @checked(old('publish_immediately', $collector->publish_immediately ?? true))> 采集后直接发布</label>

        @php $opt = $collector->options ?? []; @endphp
        <div id="jsonOpts">
        <h3 style="margin-top:20px">JSON 映射（仅 type=json）</h3>
        <label>列表路径 list_path（根数组填 . ）</label>
        <input type="text" name="list_path" value="{{ old('list_path', $opt['list_path'] ?? 'items') }}">
        <label>title_key</label>
        <input type="text" name="title_key" value="{{ old('title_key', $opt['title_key'] ?? 'title') }}">
        <label>link_key</label>
        <input type="text" name="link_key" value="{{ old('link_key', $opt['link_key'] ?? 'url') }}">
        <label>summary_key</label>
        <input type="text" name="summary_key" value="{{ old('summary_key', $opt['summary_key'] ?? 'summary') }}">
        <label>content_key</label>
        <input type="text" name="content_key" value="{{ old('content_key', $opt['content_key'] ?? 'content') }}">
        <label>guid_key（去重，默认 id）</label>
        <input type="text" name="guid_key" value="{{ old('guid_key', $opt['guid_key'] ?? 'id') }}">
        <label>published_key</label>
        <input type="text" name="published_key" value="{{ old('published_key', $opt['published_key'] ?? 'published_at') }}">
        <label>cover_key</label>
        <input type="text" name="cover_key" value="{{ old('cover_key', $opt['cover_key'] ?? 'cover') }}">
        </div>

        <div id="htmlOpts">
        <h3 style="margin-top:20px">HTML 选择器（仅 type=html）</h3>
        <p class="muted">支持标签 / #id / .class / [attr] 及后代（空格）。更复杂请用 <code>xpath://...</code> 前缀。详情页只请求与列表同源的链接，最多 30 条。</p>
        <label>条目 item_selector（如 article.post）</label>
        <input type="text" name="item_selector" value="{{ old('item_selector', $opt['item_selector'] ?? '') }}" placeholder="article.post">
        <label>链接 link_selector（相对条目，默认 a）</label>
        <input type="text" name="link_selector" value="{{ old('link_selector', $opt['link_selector'] ?? 'a') }}" placeholder="h2 a">
        <label>标题 title_selector（可空，默认用链接文字）</label>
        <input type="text" name="title_selector" value="{{ old('title_selector', $opt['title_selector'] ?? '') }}" placeholder="h2 a">
        <label>摘要 summary_selector</label>
        <input type="text" name="summary_selector" value="{{ old('summary_selector', $opt['summary_selector'] ?? '') }}" placeholder=".excerpt">
        <label>封面 cover_selector（取 src）</label>
        <input type="text" name="cover_selector" value="{{ old('cover_selector', $opt['cover_selector'] ?? '') }}" placeholder="img">
        <label>详情正文 detail_content_selector（填写后会再请求每条链接）</label>
        <input type="text" name="detail_content_selector" value="{{ old('detail_content_selector', $opt['detail_content_selector'] ?? '') }}" placeholder="article.body">
        </div>

        <p style="margin-top:16px">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.collectors.index') }}">返回</a>
        </p>
    </form>

    @if(!empty($logs) && $logs->isNotEmpty())
        <h3 style="margin-top:28px">最近日志</h3>
        <table>
            <thead><tr><th>时间</th><th>状态</th><th>拉取</th><th>新建</th><th>跳过</th><th>消息</th></tr></thead>
            <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->status }}</td>
                    <td>{{ $log->fetched }}</td>
                    <td>{{ $log->created }}</td>
                    <td>{{ $log->skipped }}</td>
                    <td class="muted">{{ $log->message }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <script>
    (function () {
        const sel = document.getElementById('collectorType');
        const jsonOpts = document.getElementById('jsonOpts');
        const htmlOpts = document.getElementById('htmlOpts');
        if (!sel) return;
        const apply = () => {
            jsonOpts.style.display = sel.value === 'json' ? '' : 'none';
            htmlOpts.style.display = sel.value === 'html' ? '' : 'none';
        };
        sel.addEventListener('change', apply);
        apply();
    })();
    </script>
@endsection
