@extends('admin.layout')

@section('title', $menu->exists ? '编辑菜单' : '新建菜单')

@section('content')
    <form method="post" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}">
        @csrf
        @if($menu->exists) @method('PUT') @endif

        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $menu->name) }}" required>

        <label>位置</label>
        <input type="text" name="location" value="{{ old('location', $menu->location ?: 'header') }}">

        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true))> 启用</label>

        <h3 style="margin-top:24px;">菜单项</h3>
        <p class="muted">留空标题的行会被忽略。可增删行后保存。</p>
        <div id="menu-items">
            @php
                $rows = old('items');
                if (! $rows) {
                    $rows = $menu->items?->map(fn ($item) => [
                        'title_zh_cn' => $item->t('title', 'zh_cn'),
                        'title_en' => $item->t('title', 'en'),
                        'type' => $item->type,
                        'url' => $item->url,
                        'reference_id' => $item->reference_id,
                    ])->all() ?: [['title_zh_cn' => '', 'title_en' => '', 'type' => 'url', 'url' => '/', 'reference_id' => '']];
                }
            @endphp
            @foreach($rows as $i => $row)
                <div class="item-row" style="border-top:1px solid #e5e7eb; padding-top:12px; margin-top:12px;">
                    <label>中文标题</label>
                    <input type="text" name="items[{{ $i }}][title_zh_cn]" value="{{ $row['title_zh_cn'] ?? '' }}">
                    <label>英文标题</label>
                    <input type="text" name="items[{{ $i }}][title_en]" value="{{ $row['title_en'] ?? '' }}">
                    <label>类型</label>
                    <select name="items[{{ $i }}][type]">
                        <option value="url" @selected(($row['type'] ?? '') === 'url')>URL</option>
                        <option value="page" @selected(($row['type'] ?? '') === 'page')>页面 ID</option>
                        <option value="post" @selected(($row['type'] ?? '') === 'post')>文章 ID</option>
                    </select>
                    <label>URL</label>
                    <input type="text" name="items[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}">
                    <label>引用 ID</label>
                    <input type="number" name="items[{{ $i }}][reference_id]" value="{{ $row['reference_id'] ?? '' }}">
                </div>
            @endforeach
        </div>
        <p style="margin-top:12px;"><button type="button" class="btn btn-muted" id="add-item">增加一行</button></p>

        <p style="margin-top:16px;">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.menus.index') }}">返回</a>
        </p>
    </form>
@endsection

@push('scripts')
<script>
let idx = {{ count($rows) }};
document.getElementById('add-item').addEventListener('click', () => {
    const wrap = document.getElementById('menu-items');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.style.cssText = 'border-top:1px solid #e5e7eb; padding-top:12px; margin-top:12px;';
    div.innerHTML = `
        <label>中文标题</label><input type="text" name="items[${idx}][title_zh_cn]">
        <label>英文标题</label><input type="text" name="items[${idx}][title_en]">
        <label>类型</label>
        <select name="items[${idx}][type]">
            <option value="url">URL</option>
            <option value="page">页面 ID</option>
            <option value="post">文章 ID</option>
        </select>
        <label>URL</label><input type="text" name="items[${idx}][url]" value="/">
        <label>引用 ID</label><input type="number" name="items[${idx}][reference_id]">
    `;
    wrap.appendChild(div);
    idx++;
});
</script>
@endpush
