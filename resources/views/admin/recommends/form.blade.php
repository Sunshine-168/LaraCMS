@extends('admin.layout')
@section('title', $recommend->exists ? '编辑推荐位' : '新建推荐位')
@section('icon', 'th-large')
@php
    $qp = old('query', $recommend->query_params ?? []);
    $type = old('type', $recommend->type ?? 'image');
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.recommends.index') }}">返回列表</a>
@endsection
@section('content')
    <form method="post" class="rec-form" action="{{ $recommend->exists ? route('admin.recommends.update', $recommend) : route('admin.recommends.store') }}">
        @csrf
        @if($recommend->exists) @method('PUT') @endif

        <label for="rec-title">名称</label>
        <input id="rec-title" type="text" name="title" value="{{ old('title', $recommend->title) }}" placeholder="如 首页轮播" required autofocus>
        <p class="muted field-hint">后台列表里看到的名字。</p>

        <label for="rec-name">标识码</label>
        <input id="rec-name" type="text" name="name" value="{{ old('name', $recommend->name) }}" placeholder="如 home_banner">
        <p class="muted field-hint">主题用 @@cmsAdv(['name' => 'home_banner']) 调用。可空时按名称生成，建好后尽量不要改。</p>

        <label for="recType">类型</label>
        <select name="type" id="recType">
            @foreach(['image'=>'图片','link'=>'链接','html'=>'HTML','entry'=>'文档条目','query'=>'自动取数'] as $t => $label)
                <option value="{{ $t }}" @selected($type === $t)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="muted field-hint">图片 / 链接 / HTML / 文档是手工条目；自动取数按下面的条件从内容里拉。</p>

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recommend->is_active ?? true))>
            启用，前台可以调用
        </label>

        <div id="queryBox" class="rec-query" style="{{ $type === 'query' ? '' : 'display:none' }}">
            <h3>取数条件</h3>
            <label>模型</label>
            <select name="query[model_id]">
                <option value="">全部模型</option>
                @foreach($models ?? [] as $m)
                    <option value="{{ $m->id }}" @selected((string) ($qp['model_id'] ?? '') === (string) $m->id)>{{ $m->name }}</option>
                @endforeach
            </select>
            <label>栏目（可多选）</label>
            <select name="query[category_ids][]" multiple size="6">
                @foreach($categories ?? [] as $c)
                    <option value="{{ $c->id }}" @selected(in_array($c->id, $qp['category_ids'] ?? [], false))>{{ $c->t('title') }}</option>
                @endforeach
            </select>
            <label>推荐属性</label>
            <input type="text" name="query[flag]" value="{{ $qp['flag'] ?? '' }}" placeholder="可空，如 recommend">
            <label>条数</label>
            <input type="number" name="query[limit]" min="1" max="100" value="{{ $qp['limit'] ?? 10 }}">
            <label>排序</label>
            <select name="query[order]">
                @foreach([
                    'published_at_desc' => '发布时间↓',
                    'published_at_asc' => '发布时间↑',
                    'sort_desc' => '排序权重↓',
                    'sort_asc' => '排序权重↑',
                    'views_desc' => '浏览量↓',
                    'id_desc' => 'ID↓',
                ] as $k => $label)
                    <option value="{{ $k }}" @selected(($qp['order'] ?? 'published_at_desc') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="muted field-hint">前台条目仍是 title / notes / image / url，来自文档。</p>
        </div>

        <div id="itemsBox" style="{{ $type === 'query' ? 'display:none' : '' }}">
            <h3>手工条目</h3>
            @php
                $localeCols = \App\Support\Locale::configured();
                $defaultCode = \App\Support\Locale::default();
                $rows = old('items');
                if (! $rows) {
                    $empty = collect($localeCols)->mapWithKeys(fn ($meta, $code) => [
                        'title_'.$code => '',
                        'notes_'.$code => '',
                    ])->all();
                    $rows = $recommend->items?->map(function ($i) use ($localeCols) {
                        $row = [
                            'image' => $i->image,
                            'url' => $i->url,
                            'html' => $i->html,
                            'entry_id' => $i->entry_id,
                        ];
                        foreach ($localeCols as $code => $meta) {
                            $row['title_'.$code] = $i->translation('title', $code);
                            $row['notes_'.$code] = $i->translation('notes', $code);
                        }

                        return $row;
                    })->all() ?: [$empty];
                }
            @endphp
            <div id="items">
                @foreach($rows as $i => $row)
                    <div class="rec-item">
                        @foreach($localeCols as $code => $meta)
                            <label>{{ $code === $defaultCode ? '标题' : $meta['label'] }}</label>
                            <input type="text" name="items[{{ $i }}][title_{{ $code }}]" value="{{ $row['title_'.$code] ?? '' }}">
                        @endforeach
                        @foreach($localeCols as $code => $meta)
                            <label>{{ $code === $defaultCode ? '备注' : $meta['label'].' · 备注' }}</label>
                            <input type="text" name="items[{{ $i }}][notes_{{ $code }}]" value="{{ $row['notes_'.$code] ?? '' }}">
                        @endforeach
                        <label>图片 URL</label>
                        <input type="text" name="items[{{ $i }}][image]" value="{{ $row['image'] ?? '' }}">
                        <label>链接</label>
                        <input type="text" name="items[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}">
                        <label>HTML</label>
                        <textarea name="items[{{ $i }}][html]">{{ $row['html'] ?? '' }}</textarea>
                        <label>关联文档 ID</label>
                        <input type="number" name="items[{{ $i }}][entry_id]" value="{{ $row['entry_id'] ?? '' }}">
                    </div>
                @endforeach
            </div>
            <p><button type="button" class="btn btn-muted" id="add">增加一条</button></p>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $recommend->exists ? '保存' : '添加推荐位' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.recommends.index') }}">取消</a>
        </div>
    </form>
@endsection
@push('scripts')
<script>
(function () {
    var idx = {{ count($rows ?? []) }};
    var localeCols = @json(collect($localeCols ?? \App\Support\Locale::configured())->map(fn ($meta, $code) => ['code' => $code, 'label' => $meta['label'] ?? $code])->values());
    var defaultCode = @json($defaultCode ?? \App\Support\Locale::default());
    document.getElementById('add')?.addEventListener('click', function () {
        var d = document.createElement('div');
        d.className = 'rec-item';
        var html = '';
        localeCols.forEach(function (loc) {
            html += '<label>' + (loc.code === defaultCode ? '标题' : loc.label) + '</label><input name="items[' + idx + '][title_' + loc.code + ']">';
        });
        html += '<label>图片 URL</label><input name="items[' + idx + '][image]"><label>链接</label><input name="items[' + idx + '][url]">';
        d.innerHTML = html;
        document.getElementById('items').appendChild(d);
        idx++;
    });
    var typeSel = document.getElementById('recType');
    function syncType() {
        var q = typeSel && typeSel.value === 'query';
        document.getElementById('queryBox').style.display = q ? '' : 'none';
        document.getElementById('itemsBox').style.display = q ? 'none' : '';
    }
    typeSel?.addEventListener('change', syncType);
})();
</script>
@endpush
