@extends('admin.layout')
@section('title', $recommend->exists ? '编辑推荐位' : '新建推荐位')
@section('content')
    @php
        $qp = old('query', $recommend->query_params ?? []);
        $type = old('type', $recommend->type ?? 'image');
    @endphp
    <form method="post" action="{{ $recommend->exists ? route('admin.recommends.update', $recommend) : route('admin.recommends.store') }}">
        @csrf
        @if($recommend->exists) @method('PUT') @endif
        <label>标识 name（主题里 @@cmsAdv name）</label>
        <input type="text" name="name" value="{{ old('name', $recommend->name) }}" required>
        <label>标题</label>
        <input type="text" name="title" value="{{ old('title', $recommend->title) }}">
        <label>类型</label>
        <select name="type" id="recType">
            @foreach(['image'=>'图片','link'=>'链接','html'=>'HTML','entry'=>'文档条目','query'=>'内容查询（自动取数）'] as $t => $label)
                <option value="{{ $t }}" @selected($type === $t)>{{ $label }}</option>
            @endforeach
        </select>
        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recommend->is_active ?? true))> 启用</label>

        <div id="queryBox" style="{{ $type === 'query' ? '' : 'display:none' }};margin-top:16px;padding:12px;border:1px solid #dee2e6;border-radius:6px">
            <h3 style="margin:0 0 10px">查询条件</h3>
            <label>模型</label>
            <select name="query[model_id]">
                <option value="">全部模型</option>
                @foreach($models ?? [] as $m)
                    <option value="{{ $m->id }}" @selected((string)($qp['model_id'] ?? '') === (string)$m->id)>{{ $m->name }}</option>
                @endforeach
            </select>
            <label>栏目（可多选）</label>
            <select name="query[category_ids][]" multiple size="6" style="min-height:120px">
                @foreach($categories ?? [] as $c)
                    <option value="{{ $c->id }}" @selected(in_array($c->id, $qp['category_ids'] ?? [], false))>{{ $c->t('title') }}</option>
                @endforeach
            </select>
            <label>Flags（如 top / recommend，可空）</label>
            <input type="text" name="query[flag]" value="{{ $qp['flag'] ?? '' }}" placeholder="recommend">
            <label>条数</label>
            <input type="number" name="query[limit]" min="1" max="100" value="{{ $qp['limit'] ?? 10 }}">
            <label>排序</label>
            <select name="query[order]">
                @foreach([
                    'published_at_desc'=>'发布时间↓','published_at_asc'=>'发布时间↑',
                    'sort_desc'=>'排序权重↓','sort_asc'=>'排序权重↑',
                    'views_desc'=>'浏览量↓','id_desc'=>'ID↓',
                ] as $k => $label)
                    <option value="{{ $k }}" @selected(($qp['order'] ?? 'published_at_desc') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="muted">前台 @@cmsAdv 仍可用，条目字段映射为 title/notes/image/url（来自文档）。</p>
        </div>

        <div id="itemsBox" style="{{ $type === 'query' ? 'display:none' : '' }}">
            <h3 style="margin-top:20px;">手工条目</h3>
            @php
                $rows = old('items');
                if (! $rows) {
                    $rows = $recommend->items?->map(fn ($i) => [
                        'title_zh_cn' => $i->t('title', 'zh_cn'),
                        'title_en' => $i->t('title', 'en'),
                        'notes_zh_cn' => $i->t('notes', 'zh_cn'),
                        'notes_en' => $i->t('notes', 'en'),
                        'image' => $i->image,
                        'url' => $i->url,
                        'html' => $i->html,
                        'entry_id' => $i->entry_id,
                    ])->all() ?: [[ ]];
                }
            @endphp
            <div id="items">
                @foreach($rows as $i => $row)
                    <div style="border-top:1px solid #e5e7eb;margin-top:12px;padding-top:12px">
                        <label>中文标题</label><input type="text" name="items[{{ $i }}][title_zh_cn]" value="{{ $row['title_zh_cn'] ?? '' }}">
                        <label>英文标题</label><input type="text" name="items[{{ $i }}][title_en]" value="{{ $row['title_en'] ?? '' }}">
                        <label>中文备注</label><input type="text" name="items[{{ $i }}][notes_zh_cn]" value="{{ $row['notes_zh_cn'] ?? '' }}">
                        <label>图片 URL</label><input type="text" name="items[{{ $i }}][image]" value="{{ $row['image'] ?? '' }}">
                        <label>链接</label><input type="text" name="items[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}">
                        <label>HTML</label><textarea name="items[{{ $i }}][html]">{{ $row['html'] ?? '' }}</textarea>
                        <label>Entry ID</label><input type="number" name="items[{{ $i }}][entry_id]" value="{{ $row['entry_id'] ?? '' }}">
                    </div>
                @endforeach
            </div>
            <p><button type="button" class="btn btn-muted" id="add">增加一行</button></p>
        </div>

        <p style="margin-top:16px"><button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.recommends.index') }}">返回</a></p>
    </form>
@endsection
@push('scripts')
<script>
let idx = {{ count($rows ?? []) }};
document.getElementById('add')?.addEventListener('click', () => {
    const d = document.createElement('div');
    d.style.cssText = 'border-top:1px solid #e5e7eb;margin-top:12px;padding-top:12px';
    d.innerHTML = `<label>中文标题</label><input name="items[${idx}][title_zh_cn]"><label>英文标题</label><input name="items[${idx}][title_en]"><label>图片 URL</label><input name="items[${idx}][image]"><label>链接</label><input name="items[${idx}][url]">`;
    document.getElementById('items').appendChild(d); idx++;
});
const typeSel = document.getElementById('recType');
function syncType() {
    const q = typeSel.value === 'query';
    document.getElementById('queryBox').style.display = q ? '' : 'none';
    document.getElementById('itemsBox').style.display = q ? 'none' : '';
}
typeSel?.addEventListener('change', syncType);
</script>
@endpush
