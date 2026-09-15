@extends('admin.layout')
@section('title', $form->exists ? '编辑表单' : '新建表单')
@section('icon', 'clipboard-list')
@php
    $fieldTypes = \App\Models\Cms\CmsForm::fieldTypes();
    $fields = old('fields', $form->fields ?? []);
    if ($fields === []) {
        $fields = [['name' => '', 'label' => '', 'type' => 'text', 'required' => false]];
    }
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.forms.index') }}">{{ admin_back('forms') }}</a>
@endsection
@section('content')
    <form method="post" class="form-def-form" action="{{ $form->exists ? route('admin.forms.update', $form) : route('admin.forms.store') }}" id="formDef">
        @csrf
        @if($form->exists) @method('PUT') @endif

        <label for="form-name">名称</label>
        <input id="form-name" type="text" name="name" value="{{ old('name', $form->name) }}" placeholder="如 联系我们" required autofocus>
        <p class="muted field-hint">后台列表和收件箱里看到的名字。</p>

        <label for="form-slug">标识码</label>
        <input id="form-slug" type="text" name="slug" value="{{ old('slug', $form->slug) }}" placeholder="如 contact">
        <p class="muted field-hint">主题用 @@cmsForm(['slug' => 'contact']) 调用，也可 POST /form/{slug}。可空时按名称生成，建好后尽量不要改。</p>

        <label class="inline">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $form->is_active ?? true))>
            启用，前台可以提交
        </label>

        <label for="form-notify">提交通知邮箱</label>
        <input id="form-notify" type="email" name="notify_email" value="{{ old('notify_email', $form->notify_email) }}" placeholder="可空，如 ops@example.com">
        <p class="muted field-hint">有人提交时发邮件。需先在设置里配好 SMTP。</p>

        <h3>字段</h3>
        <p class="muted field-hint">显示名是访客看到的。字段名给模板用。下拉 / 单选 / 多选的选项用逗号分隔，如：咨询,投诉,合作。</p>
        <table class="data" id="fieldsTable">
            <thead>
            <tr>
                <th>显示名</th>
                <th>字段名</th>
                <th>类型</th>
                <th>必填</th>
                <th>提示 / 选项</th>
                <th class="col-actions"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($fields as $i => $field)
                @php
                    $field = is_array($field) ? $field : [];
                    $choices = is_array($field['choices'] ?? null) ? implode(',', $field['choices']) : ($field['choices'] ?? '');
                    $type = $field['type'] ?? 'text';
                @endphp
                <tr class="field-row">
                    <td><input type="text" name="fields[{{ $i }}][label]" value="{{ $field['label'] ?? '' }}" placeholder="如 邮箱"></td>
                    <td><input type="text" name="fields[{{ $i }}][name]" value="{{ $field['name'] ?? '' }}" placeholder="如 email"></td>
                    <td>
                        <select name="fields[{{ $i }}][type]">
                            @foreach($fieldTypes as $t => $label)
                                <option value="{{ $t }}" @selected($type === $t)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <label class="inline"><input type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked(! empty($field['required']))> 必填</label>
                    </td>
                    <td>
                        <input type="text" name="fields[{{ $i }}][placeholder]" value="{{ $field['placeholder'] ?? '' }}" placeholder="占位提示">
                        <input type="text" name="fields[{{ $i }}][choices]" value="{{ $choices }}" placeholder="选项，逗号分隔">
                    </td>
                    <td class="actions">
                        <button type="button" class="btn-quiet remove-field">删除</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p><button type="button" class="btn btn-muted" id="addField">加字段</button></p>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $form->exists ? '保存' : '添加表单' }}</button>
            <a class="btn btn-muted" href="{{ route('admin.forms.index') }}">取消</a>
        </div>
    </form>
@endsection
@push('scripts')
<script>
(function () {
    var tbody = document.querySelector('#fieldsTable tbody');
    var idx = tbody.querySelectorAll('tr').length;
    var types = @json($fieldTypes);
    var typeHtml = Object.keys(types).map(function (t) {
        return '<option value="' + t + '">' + types[t] + '</option>';
    }).join('');
    document.getElementById('addField').addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.className = 'field-row';
        tr.innerHTML =
            '<td><input type="text" name="fields[' + idx + '][label]" placeholder="如 邮箱"></td>' +
            '<td><input type="text" name="fields[' + idx + '][name]" placeholder="如 email"></td>' +
            '<td><select name="fields[' + idx + '][type]">' + typeHtml + '</select></td>' +
            '<td><label class="inline"><input type="checkbox" name="fields[' + idx + '][required]" value="1"> 必填</label></td>' +
            '<td><input type="text" name="fields[' + idx + '][placeholder]" placeholder="占位提示">' +
            '<input type="text" name="fields[' + idx + '][choices]" placeholder="选项，逗号分隔"></td>' +
            '<td class="actions"><button type="button" class="btn-quiet remove-field">删除</button></td>';
        tbody.appendChild(tr);
        idx++;
    });
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-field');
        if (!btn) return;
        var rows = tbody.querySelectorAll('tr');
        if (rows.length <= 1) return;
        btn.closest('tr').remove();
    });
})();
</script>
@endpush
