@extends('admin.layout')

@section('content')
    <section class="card">
        <form method="post" action="{{ route('admin.config.store') }}">
            @csrf

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div>
                    <label>字段类型</label>
                    <select name="type" id="config-type" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', 'text') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>分组</label>
                    <input list="config-group-list" name="group" value="{{ old('group', 'more') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <datalist id="config-group-list">
                        @foreach($groups as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label>字段名</label>
                    <input name="name" value="{{ old('name') }}" placeholder="仅支持字母数字下划线" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>标题</label>
                    <input name="title" value="{{ old('title') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label>默认值</label>
                    <input name="value" value="{{ old('value') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div id="options-block" style="grid-column:1 / -1;{{ in_array(old('type'), ['radio','checkbox','select','selects'], true) ? '' : 'display:none;' }}">
                    <label>选项列表</label>
                    <textarea name="data_list" rows="5" placeholder="key1|标题1&#10;key2|标题2" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ old('data_list') }}</textarea>
                </div>
                <div id="setting-ext-block">
                    <label>限制后缀</label>
                    <input name="setting_ext" value="{{ old('setting_ext') }}" placeholder="png|jpg|zip" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div id="setting-filesize-block">
                    <label>限制大小(字节)</label>
                    <input name="setting_filesize" type="number" value="{{ old('setting_filesize') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div id="max-number-block">
                    <label>最大数量</label>
                    <input name="max_number" type="number" value="{{ old('max_number', 0) }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div id="array-key-block">
                    <label>键名别称</label>
                    <input name="array_key_name" value="{{ old('array_key_name', '键名') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div id="array-value-block">
                    <label>键值别称</label>
                    <input name="array_value_name" value="{{ old('array_value_name', '键值') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label>验证规则</label>
                    <input name="rules" value="{{ old('rules') }}" placeholder="例如 required,max:255" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>提示文字</label>
                    <input name="tips" value="{{ old('tips') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div>
                    <label>错误提示</label>
                    <input name="error_tips" value="{{ old('error_tips') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label>扩展 HTML 属性</label>
                    <textarea name="extend" rows="3" placeholder='例如 data-title="test",class="form-control"' style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ old('extend') }}</textarea>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">创建配置项</button>
                <a href="{{ route('admin.config.index') }}" class="btn btn-secondary">返回配置页</a>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const typeSelect = document.getElementById('config-type');
            const optionsBlock = document.getElementById('options-block');
            const settingExt = document.getElementById('setting-ext-block');
            const settingFilesize = document.getElementById('setting-filesize-block');
            const maxNumber = document.getElementById('max-number-block');
            const arrayKey = document.getElementById('array-key-block');
            const arrayValue = document.getElementById('array-value-block');

            function refresh() {
                const type = typeSelect.value;
                const optionTypes = ['radio', 'checkbox', 'select', 'selects'];
                const fileTypes = ['image', 'images', 'downfile', 'downfiles'];
                const multiTypes = ['images', 'downfiles'];
                const isArray = type === 'array';

                optionsBlock.style.display = optionTypes.includes(type) ? '' : 'none';
                settingExt.style.display = fileTypes.includes(type) ? '' : 'none';
                settingFilesize.style.display = fileTypes.includes(type) ? '' : 'none';
                maxNumber.style.display = multiTypes.includes(type) ? '' : 'none';
                arrayKey.style.display = isArray ? '' : 'none';
                arrayValue.style.display = isArray ? '' : 'none';
            }

            typeSelect.addEventListener('change', refresh);
            refresh();
        })();
    </script>
@endsection
