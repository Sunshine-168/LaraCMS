@extends('admin.layout')

@php
    $tabKeys = array_keys($groups);
@endphp

@section('content')
    <section class="card">
        <form method="post" action="{{ route('admin.config.index') }}">
            @csrf
            <input type="hidden" name="active_tab" id="active-tab-input" value="{{ $activeTab }}">

            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                @foreach($groups as $key => $label)
                    <button
                        type="button"
                        class="btn {{ $activeTab === $key ? '' : 'btn-secondary' }}"
                        data-tab-btn="{{ $key }}"
                    >{{ $label }}</button>
                @endforeach
                <button
                    type="button"
                    class="btn {{ $activeTab === 'group' ? '' : 'btn-secondary' }}"
                    data-tab-btn="group"
                >分组配置</button>
                </div>
                <a href="{{ route('admin.config.create') }}" class="btn">新增配置项</a>
            </div>

            @foreach($groups as $key => $label)
                <div data-tab-panel="{{ $key }}" style="{{ $activeTab === $key ? '' : 'display:none;' }}">
                    @foreach(($configs[$key] ?? collect()) as $config)
                        <div class="card" style="margin-bottom:12px;padding:16px;">
                            <div style="display:grid;grid-template-columns:220px 1fr 220px;gap:16px;align-items:start;">
                                <div>
                                    <div style="font-weight:600;">{{ $config['title'] }}</div>
                                    <div class="meta" style="margin-top:6px;">{{ $config['name'] }}</div>
                                </div>
                                <div>
                                    @switch($config['type'])
                                        @case('text')
                                        @case('number')
                                            <input
                                                name="row[{{ $config['name'] }}]"
                                                type="{{ $config['type'] === 'number' ? 'number' : 'text' }}"
                                                value="{{ $config['value'] }}"
                                                style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"
                                            >
                                            @break

                                        @case('textarea')
                                        @case('editor')
                                            <textarea
                                                name="row[{{ $config['name'] }}]"
                                                style="width:100%;min-height:100px;padding:10px;border:1px solid #d1d5db;border-radius:8px;"
                                            >{{ $config['value'] }}</textarea>
                                            @break

                                        @case('radio')
                                            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                                                @foreach($config['data_list_array'] as $optionValue => $optionLabel)
                                                    <label style="display:flex;gap:6px;align-items:center;">
                                                        <input type="radio" name="row[{{ $config['name'] }}]" value="{{ $optionValue }}" @checked((string) $config['value'] === (string) $optionValue)>
                                                        <span>{{ $optionLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @break

                                        @case('checkbox')
                                            @php $checkedValues = array_filter(explode('|', (string) $config['value'])); @endphp
                                            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                                                @foreach($config['data_list_array'] as $optionValue => $optionLabel)
                                                    <label style="display:flex;gap:6px;align-items:center;">
                                                        <input type="checkbox" name="row[{{ $config['name'] }}][]" value="{{ $optionValue }}" @checked(in_array((string) $optionValue, array_map('strval', $checkedValues), true))>
                                                        <span>{{ $optionLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @break

                                        @case('select')
                                        @case('selects')
                                            @php $selectedValues = array_filter(explode('|', (string) $config['value'])); @endphp
                                            <select
                                                name="row[{{ $config['name'] }}]{{ $config['type'] === 'selects' ? '[]' : '' }}"
                                                {{ $config['type'] === 'selects' ? 'multiple' : '' }}
                                                style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"
                                            >
                                                @foreach($config['data_list_array'] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', $selectedValues), true))>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                            @break

                                        @case('image')
                                        @case('images')
                                        @case('downfile')
                                        @case('downfiles')
                                            @php
                                                $isImage = in_array($config['type'], ['image', 'images'], true);
                                                $multiple = in_array($config['type'], ['images', 'downfiles'], true) ? 'true' : 'false';
                                                $mimetype = $isImage ? 'image/' : ($config['setting_array']['ext'] ?? '*');
                                            @endphp
                                            <div style="display:flex;gap:8px;">
                                                <input
                                                    id="config-field-{{ $config['name'] }}"
                                                    name="row[{{ $config['name'] }}]"
                                                    value="{{ $config['value'] }}"
                                                    style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"
                                                >
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    onclick="openConfigAttachmentSelector('config-field-{{ $config['name'] }}', '{{ $multiple }}', '{{ $mimetype }}')"
                                                >选择</button>
                                            </div>
                                            @break

                                        @case('array')
                                            <div class="array-field" data-array-name="{{ $config['name'] }}">
                                                <div class="array-rows">
                                                    @forelse($config['value_array'] as $arrayKey => $arrayValue)
                                                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;">
                                                            <input name="row[{{ $config['name'] }}][key][]" value="{{ $arrayKey }}" placeholder="键" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                                            <input name="row[{{ $config['name'] }}][value][]" value="{{ $arrayValue }}" placeholder="值" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                                            <button type="button" class="btn btn-secondary" onclick="this.parentNode.remove()">删除</button>
                                                        </div>
                                                    @empty
                                                        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;">
                                                            <input name="row[{{ $config['name'] }}][key][]" placeholder="键" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                                            <input name="row[{{ $config['name'] }}][value][]" placeholder="值" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                                            <button type="button" class="btn btn-secondary" onclick="this.parentNode.remove()">删除</button>
                                                        </div>
                                                    @endforelse
                                                </div>
                                                <button type="button" class="btn btn-secondary" onclick="appendArrayRow('{{ $config['name'] }}')">追加</button>
                                            </div>
                                            @break

                                        @default
                                            <input
                                                name="row[{{ $config['name'] }}]"
                                                value="{{ $config['value'] }}"
                                                style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"
                                            >
                                    @endswitch
                                </div>
                                <div class="meta">
                                    @if(!empty($config['tips']))
                                        <div>{{ $config['tips'] }}</div>
                                    @endif
                                    <div style="margin-top:8px;">模板变量：`{{ '{' }}$site.{{ $config['name'] }}{{ '}' }}`</div>
                                    @if((int) ($config['is_default'] ?? 1) === 0)
                                        <form method="post" action="{{ route('admin.config.destroy', $config['id']) }}" onsubmit="return confirm('确认删除该配置项吗？');" style="margin-top:8px;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary" style="color:#b91c1c;">删除</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div data-tab-panel="group" style="{{ $activeTab === 'group' ? '' : 'display:none;' }}">
                <section class="card" style="padding:16px;">
                    <div class="meta" style="margin-bottom:12px;">分组键和值会写回 `group` 配置项。</div>
                    <div id="group-config-rows">
                        @foreach($groups as $key => $label)
                            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;">
                                <input name="group[key][]" value="{{ $key }}" placeholder="键名" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                <input name="group[value][]" value="{{ $label }}" placeholder="显示名称" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                <button type="button" class="btn btn-secondary" onclick="this.parentNode.remove()">删除</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="appendGroupRow()">追加分组</button>
                </section>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">保存配置</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">返回控制台</a>
            </div>
        </form>
    </section>

    <template id="array-row-template">
        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;">
            <input placeholder="键" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            <input placeholder="值" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
            <button type="button" class="btn btn-secondary" onclick="this.parentNode.remove()">删除</button>
        </div>
    </template>

    <script>
        (function () {
            const activeInput = document.getElementById('active-tab-input');
            document.querySelectorAll('[data-tab-btn]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const tab = this.getAttribute('data-tab-btn');
                    activeInput.value = tab;

                    document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                        panel.style.display = panel.getAttribute('data-tab-panel') === tab ? '' : 'none';
                    });

                    document.querySelectorAll('[data-tab-btn]').forEach(function (btn) {
                        btn.classList.add('btn-secondary');
                    });

                    this.classList.remove('btn-secondary');
                });
            });
        })();

        function appendGroupRow() {
            const container = document.getElementById('group-config-rows');
            const row = document.createElement('div');
            row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;';
            row.innerHTML = '<input name="group[key][]" placeholder="键名" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">'
                + '<input name="group[value][]" placeholder="显示名称" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">'
                + '<button type="button" class="btn btn-secondary" onclick="this.parentNode.remove()">删除</button>';
            container.appendChild(row);
        }

        function appendArrayRow(name) {
            const container = document.querySelector('.array-field[data-array-name="' + name + '"] .array-rows');
            const row = document.createElement('div');
            row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;';
            row.innerHTML = '<input name="row[' + name + '][key][]" placeholder="键" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">'
                + '<input name="row[' + name + '][value][]" placeholder="值" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">'
                + '<button type="button" class="btn btn-secondary" onclick="this.parentNode.remove()">删除</button>';
            container.appendChild(row);
        }

        function openConfigAttachmentSelector(fieldId, multiple, mimetype) {
            const url = new URL(@json(route('admin.attachments.select')), window.location.origin);
            url.searchParams.set('field', fieldId);
            url.searchParams.set('multiple', multiple);
            url.searchParams.set('mimetype', mimetype);
            const win = window.open(url.toString(), '_blank', 'width=1100,height=760');
            window.addEventListener('message', function handler(event) {
                if (!event.data || event.data.type !== 'attachment:selected') {
                    return;
                }
                const target = document.getElementById(fieldId);
                if (target) {
                    target.value = multiple === 'true' ? (event.data.paths || []).join(',') : ((event.data.paths || [])[0] || '');
                }
                window.removeEventListener('message', handler);
                if (win && !win.closed) {
                    win.close();
                }
            });
        }
    </script>
@endsection
