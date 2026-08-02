@extends('admin.layout')
@section('title', $collector->exists ? '编辑采集器' : '新建采集器')
@section('content')
    <form method="post" action="{{ $collector->exists ? route('admin.collectors.update', $collector) : route('admin.collectors.store') }}">
        @csrf
        @if($collector->exists) @method('PUT') @endif

        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $collector->name) }}" required>

        <label>类型</label>
        <select name="type">
            <option value="rss" @selected(old('type', $collector->type) === 'rss')>RSS / Atom</option>
            <option value="json" @selected(old('type', $collector->type) === 'json')>JSON 列表</option>
        </select>

        <label>源地址 URL（请仅采集你有权使用的公开源）</label>
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

        <h3 style="margin-top:20px">JSON 映射（仅 type=json）</h3>
        @php $opt = $collector->options ?? []; @endphp
        <label>列表路径 list_path</label>
        <input type="text" name="list_path" value="{{ old('list_path', $opt['list_path'] ?? 'items') }}">
        <label>title_key</label>
        <input type="text" name="title_key" value="{{ old('title_key', $opt['title_key'] ?? 'title') }}">
        <label>link_key</label>
        <input type="text" name="link_key" value="{{ old('link_key', $opt['link_key'] ?? 'url') }}">
        <label>summary_key</label>
        <input type="text" name="summary_key" value="{{ old('summary_key', $opt['summary_key'] ?? 'summary') }}">
        <label>content_key</label>
        <input type="text" name="content_key" value="{{ old('content_key', $opt['content_key'] ?? 'content') }}">

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
@endsection
