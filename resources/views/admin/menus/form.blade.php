@extends('admin.layout')
@section('title', $menu->exists ? '编辑菜单' : '新建菜单')
@section('icon', 'bars')
@php
    $location = old('location', $menu->location ?: 'header');
    $categories = $categories ?? collect();
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.menus.index') }}">返回列表</a>
@endsection
@section('content')
    <form method="post" class="menu-form" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}">
        @csrf
        @if($menu->exists) @method('PUT') @endif

        <label for="menu-name">名称</label>
        <input id="menu-name" type="text" name="name" value="{{ old('name', $menu->name) }}" placeholder="如 主导航" required autofocus>
        <p class="muted field-hint">后台列表里看到的名字。</p>

        <label for="menu-loc">出现位置</label>
        <input id="menu-loc" type="text" name="location" list="menu-locations" value="{{ $location }}" placeholder="header" required>
        <datalist id="menu-locations">
            @foreach(\App\Models\Cms\Menu::locations() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </datalist>
        <p class="muted field-hint">主题用 @@cmsMenu(['location' => 'header']) 读取。常用：页头 header、页脚 footer、侧栏 sidebar。没有启用的菜单时前台回退到栏目导航。</p>

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true))>
            启用，前台按这个位置显示
        </label>

        <h3>菜单项</h3>
        <p class="muted field-hint">链接填网址；栏目从列表里选；文档填内容 ID。上级留空就是顶级。标题都空的行会忽略。</p>
        @php
            $rows = old('items');
            if (! $rows) {
                $idToIndex = [];
                $items = $menu->items?->values() ?? collect();
                foreach ($items as $idx => $item) {
                    $idToIndex[$item->id] = $idx;
                }
                $rows = $items->map(fn ($item) => [
                    'title_zh_cn' => $item->t('title', 'zh_cn'),
                    'title_en' => $item->t('title', 'en'),
                    'type' => $item->type,
                    'url' => $item->url,
                    'reference_id' => $item->reference_id,
                    'parent_index' => ($item->parent_id && isset($idToIndex[$item->parent_id])) ? $idToIndex[$item->parent_id] : '',
                ])->all() ?: [['title_zh_cn' => '', 'title_en' => '', 'type' => 'url', 'url' => '/', 'reference_id' => '', 'parent_index' => '']];
            }
            $rowTitles = [];
            foreach ($rows as $ri => $r) {
                $rowTitles[$ri] = trim((string) ($r['title_zh_cn'] ?? '')) ?: ('第 '.($ri + 1).' 条');
            }
            $catOptionsJson = $categories->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => str_repeat('— ', (int) ($c->depth ?? 0)).$c->t('title'),
                ];
            })->values();
        @endphp
        <div id="menu-items">
            @foreach($rows as $i => $row)
                @php $type = $row['type'] ?? 'url'; @endphp
                <div class="menu-item" data-row="{{ $i }}">
                    <label>中文标题</label>
                    <input type="text" name="items[{{ $i }}][title_zh_cn]" value="{{ $row['title_zh_cn'] ?? '' }}">
                    <label>英文标题</label>
                    <input type="text" name="items[{{ $i }}][title_en]" value="{{ $row['title_en'] ?? '' }}">
                    <label>类型</label>
                    <select name="items[{{ $i }}][type]" class="menu-item-type">
                        <option value="url" @selected($type === 'url')>链接</option>
                        <option value="category" @selected($type === 'category')>栏目</option>
                        <option value="entry" @selected($type === 'entry')>文档</option>
                    </select>
                    <div class="menu-item-target" data-for="url" @style(['display:none' => $type !== 'url'])>
                        <label>链接</label>
                        <input type="text" name="items[{{ $i }}][url]" value="{{ $row['url'] ?? '/' }}">
                    </div>
                    <div class="menu-item-target" data-for="category" @style(['display:none' => $type !== 'category'])>
                        <label>栏目</label>
                        <select name="items[{{ $i }}][category_id]" class="menu-item-category">
                            <option value="">选择栏目</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @selected((string) ($row['reference_id'] ?? '') === (string) $c->id)>{{ str_repeat('— ', (int) ($c->depth ?? 0)).$c->t('title') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="menu-item-target" data-for="entry" @style(['display:none' => $type !== 'entry'])>
                        <label>文档 ID</label>
                        <input type="number" name="items[{{ $i }}][entry_id]" value="{{ ($type === 'entry') ? ($row['reference_id'] ?? '') : '' }}">
                    </div>
                    <input type="hidden" name="items[{{ $i }}][reference_id]" class="menu-item-ref" value="{{ $row['reference_id'] ?? '' }}">
                    <label>上级</label>
                    <select name="items[{{ $i }}][parent_index]">
                        <option value="">顶级</option>
                        @for($p = 0; $p < $i; $p++)
                            <option value="{{ $p }}" @selected((string) ($row['parent_index'] ?? '') === (string) $p)>{{ $rowTitles[$p] ?? ('第 '.($p + 1).' 条') }}</option>
                        @endfor
                    </select>
                </div>
            @endforeach
        </div>
        <p><button type="button" class="btn btn-muted" id="add-item">增加一条</button></p>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $menu->exists ? '保存' : '添加菜单' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.menus.index') }}">取消</a>
        </div>
    </form>
@endsection
@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('menu-items');
    var idx = {{ count($rows) }};
    var catOptions = @json($catOptionsJson);

    function catSelectHtml(name, selected) {
        var html = '<select name="' + name + '" class="menu-item-category"><option value="">选择栏目</option>';
        catOptions.forEach(function (c) {
            html += '<option value="' + c.id + '"' + (String(selected) === String(c.id) ? ' selected' : '') + '>' + c.title + '</option>';
        });
        return html + '</select>';
    }

    function syncRow(row) {
        var type = row.querySelector('.menu-item-type');
        var val = type ? type.value : 'url';
        row.querySelectorAll('.menu-item-target').forEach(function (box) {
            box.style.display = box.getAttribute('data-for') === val ? '' : 'none';
        });
        var ref = row.querySelector('.menu-item-ref');
        if (!ref) return;
        if (val === 'category') {
            var cat = row.querySelector('.menu-item-category');
            ref.value = cat ? cat.value : '';
        } else if (val === 'entry') {
            var ent = row.querySelector('input[name$="[entry_id]"]');
            ref.value = ent ? ent.value : '';
        } else {
            ref.value = '';
        }
    }

    function bindRow(row) {
        row.querySelector('.menu-item-type')?.addEventListener('change', function () { syncRow(row); });
        row.querySelector('.menu-item-category')?.addEventListener('change', function () { syncRow(row); });
        row.querySelector('input[name$="[entry_id]"]')?.addEventListener('input', function () { syncRow(row); });
        syncRow(row);
    }

    wrap?.querySelectorAll('.menu-item').forEach(bindRow);
    document.querySelector('.menu-form')?.addEventListener('submit', function () {
        wrap?.querySelectorAll('.menu-item').forEach(syncRow);
    });

    document.getElementById('add-item')?.addEventListener('click', function () {
        var parents = '<option value="">顶级</option>';
        wrap.querySelectorAll('.menu-item').forEach(function (row, p) {
            var title = row.querySelector('input[name$="[title_zh_cn]"]');
            var label = (title && title.value.trim()) ? title.value.trim() : ('第 ' + (p + 1) + ' 条');
            parents += '<option value="' + p + '">' + label.replace(/[&<>"]/g, '') + '</option>';
        });
        var div = document.createElement('div');
        div.className = 'menu-item';
        div.setAttribute('data-row', String(idx));
        div.innerHTML =
            '<label>中文标题</label><input type="text" name="items[' + idx + '][title_zh_cn]">' +
            '<label>英文标题</label><input type="text" name="items[' + idx + '][title_en]">' +
            '<label>类型</label><select name="items[' + idx + '][type]" class="menu-item-type">' +
            '<option value="url">链接</option><option value="category">栏目</option><option value="entry">文档</option></select>' +
            '<div class="menu-item-target" data-for="url"><label>链接</label><input type="text" name="items[' + idx + '][url]" value="/"></div>' +
            '<div class="menu-item-target" data-for="category" style="display:none"><label>栏目</label>' + catSelectHtml('items[' + idx + '][category_id]', '') + '</div>' +
            '<div class="menu-item-target" data-for="entry" style="display:none"><label>文档 ID</label><input type="number" name="items[' + idx + '][entry_id]"></div>' +
            '<input type="hidden" name="items[' + idx + '][reference_id]" class="menu-item-ref" value="">' +
            '<label>上级</label><select name="items[' + idx + '][parent_index]">' + parents + '</select>';
        wrap.appendChild(div);
        bindRow(div);
        idx++;
    });
})();
</script>
@endpush
