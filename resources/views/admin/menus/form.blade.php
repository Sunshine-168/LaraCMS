@extends('admin.layout')
@section('title', $menu->exists ? '编辑菜单' : '新建菜单')
@section('icon', 'bars')
@php
    $location = old('location', $menu->location ?: 'header');
    $categories = $categories ?? collect();
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.menus.index') }}">{{ admin_back('menus') }}</a>
@endsection
@section('content')
    <form method="post" class="menu-form" action="{{ $menu->exists ? route('admin.menus.update', $menu) : route('admin.menus.store') }}">
        @csrf
        @if($menu->exists) @method('PUT') @endif

        <div class="menu-meta">
            <div>
                <label for="menu-name">名称</label>
                <input id="menu-name" type="text" name="name" value="{{ old('name', $menu->name) }}" placeholder="如 主导航" required autofocus>
            </div>
            <div>
                <label for="menu-loc">出现位置</label>
                <input id="menu-loc" type="text" name="location" list="menu-locations" value="{{ $location }}" placeholder="header" required>
                <datalist id="menu-locations">
                    @foreach(\App\Models\Cms\Menu::locations() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </datalist>
            </div>
        </div>
        <p class="muted field-hint">主题用 @@cmsMenu(['location' => 'header']) 读取。常用：页头 header、页脚 footer。没有启用的菜单时前台回退到栏目导航。</p>
        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true))>
            启用，前台按这个位置显示
        </label>

        <h3>菜单项</h3>
        <p class="muted field-hint">一条一行。其它语言可空，保存时按默认语言补。上级选另一条，就是它的子菜单。</p>
        @php
            $localeCols = \App\Support\Locale::configured();
            $defaultCode = \App\Support\Locale::default();
            $rows = old('items');
            if (! $rows) {
                $idToIndex = [];
                $items = $menu->items?->values() ?? collect();
                foreach ($items as $idx => $item) {
                    $idToIndex[$item->id] = $idx;
                }
                $emptyTitles = collect($localeCols)->mapWithKeys(fn ($meta, $code) => ['title_'.$code => ''])->all();
                $rows = $items->map(function ($item) use ($localeCols, $idToIndex) {
                    $row = [
                        'type' => $item->type,
                        'url' => $item->url,
                        'reference_id' => $item->reference_id,
                        'parent_index' => ($item->parent_id && isset($idToIndex[$item->parent_id])) ? $idToIndex[$item->parent_id] : '',
                    ];
                    foreach ($localeCols as $code => $meta) {
                        $row['title_'.$code] = $item->translation('title', $code);
                    }

                    return $row;
                })->all() ?: [array_merge($emptyTitles, ['type' => 'url', 'url' => '/', 'reference_id' => '', 'parent_index' => ''])];
            }
            $rowTitles = [];
            foreach ($rows as $ri => $r) {
                $rowTitles[$ri] = trim((string) ($r['title_'.$defaultCode] ?? $r['title_zh_cn'] ?? '')) ?: ('第 '.($ri + 1).' 条');
            }
            $catOptionsJson = $categories->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => str_repeat('— ', (int) ($c->depth ?? 0)).$c->t('title'),
                ];
            })->values();
        @endphp
        <div class="menu-items-wrap">
            <table class="data menu-items-table" id="menu-items">
                <thead>
                <tr>
                    @foreach($localeCols as $code => $meta)
                        <th>{{ $code === $defaultCode ? '标题' : ($meta['label'] ?? $code) }}</th>
                    @endforeach
                    <th>指向</th>
                    <th>上级</th>
                    <th class="col-actions"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $i => $row)
                    @php $type = $row['type'] ?? 'url'; @endphp
                    <tr class="menu-item" data-row="{{ $i }}">
                        @foreach($localeCols as $code => $meta)
                            <td>
                                <input type="text" name="items[{{ $i }}][title_{{ $code }}]" value="{{ $row['title_'.$code] ?? '' }}" placeholder="{{ $code === $defaultCode ? '标题' : '可空' }}" aria-label="{{ $code === $defaultCode ? '标题' : ($meta['label'] ?? $code) }}">
                            </td>
                        @endforeach
                        <td class="menu-item-point">
                            <select name="items[{{ $i }}][type]" class="menu-item-type" aria-label="类型">
                                <option value="url" @selected($type === 'url')>链接</option>
                                <option value="category" @selected($type === 'category')>栏目</option>
                                <option value="entry" @selected($type === 'entry')>文档</option>
                            </select>
                            <div class="menu-item-target" data-for="url" @style(['display:none' => $type !== 'url'])>
                                <input type="text" name="items[{{ $i }}][url]" value="{{ $row['url'] ?? '/' }}" placeholder="网址" aria-label="链接">
                            </div>
                            <div class="menu-item-target" data-for="category" @style(['display:none' => $type !== 'category'])>
                                <select name="items[{{ $i }}][category_id]" class="menu-item-category" aria-label="栏目">
                                    <option value="">选择栏目</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" @selected((string) ($row['reference_id'] ?? '') === (string) $c->id)>{{ str_repeat('— ', (int) ($c->depth ?? 0)).$c->t('title') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="menu-item-target" data-for="entry" @style(['display:none' => $type !== 'entry'])>
                                <input type="number" name="items[{{ $i }}][entry_id]" value="{{ ($type === 'entry') ? ($row['reference_id'] ?? '') : '' }}" placeholder="文档 ID" aria-label="文档">
                            </div>
                            <input type="hidden" name="items[{{ $i }}][reference_id]" class="menu-item-ref" value="{{ $row['reference_id'] ?? '' }}">
                        </td>
                        <td>
                            <select name="items[{{ $i }}][parent_index]" class="menu-item-parent" aria-label="上级">
                                <option value="">顶级</option>
                                @for($p = 0; $p < $i; $p++)
                                    <option value="{{ $p }}" @selected((string) ($row['parent_index'] ?? '') === (string) $p)>{{ $rowTitles[$p] ?? ('第 '.($p + 1).' 条') }}</option>
                                @endfor
                            </select>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn-quiet remove-item">删除</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p><button type="button" class="btn btn-muted" id="add-item">增加一条</button></p>

        <div class="form-actions menu-form-actions">
            <button class="btn" type="submit">{{ $menu->exists ? '保存' : '添加菜单' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.menus.index') }}">取消</a>
        </div>
    </form>
@endsection
@push('scripts')
<script>
(function () {
    var table = document.getElementById('menu-items');
    var body = table && table.querySelector('tbody');
    var idx = {{ count($rows) }};
    var catOptions = @json($catOptionsJson);
    var localeCols = @json(collect($localeCols)->map(fn ($meta, $code) => ['code' => $code, 'label' => $meta['label'] ?? $code])->values());
    var defaultCode = @json($defaultCode);

    function catSelectHtml(name, selected) {
        var html = '<select name="' + name + '" class="menu-item-category" aria-label="栏目"><option value="">选择栏目</option>';
        catOptions.forEach(function (c) {
            html += '<option value="' + c.id + '"' + (String(selected) === String(c.id) ? ' selected' : '') + '>' + c.title + '</option>';
        });
        return html + '</select>';
    }

    function rows() {
        return body ? Array.prototype.slice.call(body.querySelectorAll('.menu-item')) : [];
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

    rows().forEach(bindRow);
    document.querySelector('.menu-form')?.addEventListener('submit', function () {
        rows().forEach(syncRow);
    });

    document.getElementById('add-item')?.addEventListener('click', function () {
        var parents = '<option value="">顶级</option>';
        rows().forEach(function (row, p) {
            var title = row.querySelector('input[name$="[title_' + defaultCode + ']"]');
            var label = (title && title.value.trim()) ? title.value.trim() : ('第 ' + (p + 1) + ' 条');
            parents += '<option value="' + p + '">' + label.replace(/[&<>"]/g, '') + '</option>';
        });
        var titleCells = '';
        localeCols.forEach(function (loc) {
            var isDefault = loc.code === defaultCode;
            titleCells += '<td><input type="text" name="items[' + idx + '][title_' + loc.code + ']" placeholder="' + (isDefault ? '标题' : '可空') + '" aria-label="' + (isDefault ? '标题' : loc.label) + '"></td>';
        });
        var tr = document.createElement('tr');
        tr.className = 'menu-item';
        tr.setAttribute('data-row', String(idx));
        tr.innerHTML =
            titleCells +
            '<td class="menu-item-point">' +
            '<select name="items[' + idx + '][type]" class="menu-item-type" aria-label="类型">' +
            '<option value="url">链接</option><option value="category">栏目</option><option value="entry">文档</option></select>' +
            '<div class="menu-item-target" data-for="url"><input type="text" name="items[' + idx + '][url]" value="/" placeholder="网址" aria-label="链接"></div>' +
            '<div class="menu-item-target" data-for="category" style="display:none">' + catSelectHtml('items[' + idx + '][category_id]', '') + '</div>' +
            '<div class="menu-item-target" data-for="entry" style="display:none"><input type="number" name="items[' + idx + '][entry_id]" placeholder="文档 ID" aria-label="文档"></div>' +
            '<input type="hidden" name="items[' + idx + '][reference_id]" class="menu-item-ref" value="">' +
            '</td>' +
            '<td><select name="items[' + idx + '][parent_index]" class="menu-item-parent" aria-label="上级">' + parents + '</select></td>' +
            '<td class="actions"><button type="button" class="btn-quiet remove-item">删除</button></td>';
        body.appendChild(tr);
        bindRow(tr);
        idx++;
        tr.querySelector('input[name$="[title_' + defaultCode + ']"]')?.focus();
    });

    body?.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-item');
        if (!btn) return;
        var list = rows();
        if (list.length <= 1) {
            var row = btn.closest('.menu-item');
            row.querySelectorAll('input[type=text], input[type=number]').forEach(function (el) { el.value = ''; });
            var type = row.querySelector('.menu-item-type');
            if (type) type.value = 'url';
            var parent = row.querySelector('.menu-item-parent');
            if (parent) parent.value = '';
            syncRow(row);
            return;
        }
        btn.closest('.menu-item').remove();
    });
})();
</script>
@endpush
