@extends('admin.layout')
@section('title', $form->exists ? '编辑表单' : '新建表单')
@section('icon', 'wpforms')
@section('content')
    <form method="post" action="{{ $form->exists ? route('admin.forms.update', $form) : route('admin.forms.store') }}" id="formDef">
        @csrf
        @if($form->exists) @method('PUT') @endif

        <label>名称</label>
        <input type="text" name="name" value="{{ old('name', $form->name) }}" required>

        <label>标识 slug（英文/数字/横线，用于 @@cmsForm 与 /form/{slug}）</label>
        <input type="text" name="slug" value="{{ old('slug', $form->slug) }}" placeholder="contact">

        <label class="inline"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $form->is_active ?? true))> 启用</label>
        <label>提交通知邮箱（可选，需先在站点设置配置 SMTP）</label>
        <input type="email" name="notify_email" value="{{ old('notify_email', $form->notify_email) }}" placeholder="ops@example.com">

        <h3 style="margin:20px 0 10px">字段</h3>
        <p class="muted">选项类字段在「选项」里用英文逗号分隔，如：咨询,投诉,合作</p>
        <table class="data" id="fieldsTable">
            <thead>
            <tr>
                <th>标识 name</th><th>标签</th><th>类型</th><th>必填</th><th>占位/选项</th><th></th>
            </tr>
            </thead>
            <tbody>
            @php
                $fields = old('fields', $form->fields ?? []);
                if ($fields === []) {
                    $fields = [['name'=>'','label'=>'','type'=>'text','required'=>false]];
                }
            @endphp
            @foreach($fields as $i => $field)
                @php
                    $field = is_array($field) ? $field : [];
                    $choices = is_array($field['choices'] ?? null) ? implode(',', $field['choices']) : ($field['choices'] ?? '');
                @endphp
                <tr class="field-row">
                    <td><input type="text" name="fields[{{ $i }}][name]" value="{{ $field['name'] ?? '' }}" placeholder="email" style="max-width:140px"></td>
                    <td><input type="text" name="fields[{{ $i }}][label]" value="{{ $field['label'] ?? '' }}" placeholder="邮箱" style="max-width:140px"></td>
                    <td>
                        <select name="fields[{{ $i }}][type]" style="max-width:120px">
                            @foreach(['text','email','tel','number','textarea','select','radio','checkbox'] as $t)
                                <option value="{{ $t }}" @selected(($field['type'] ?? 'text') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td style="text-align:center">
                        <input type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked(!empty($field['required']))>
                    </td>
                    <td>
                        <input type="text" name="fields[{{ $i }}][placeholder]" value="{{ $field['placeholder'] ?? '' }}" placeholder="占位提示" style="max-width:120px;margin-bottom:4px">
                        <input type="text" name="fields[{{ $i }}][choices]" value="{{ $choices }}" placeholder="选项A,选项B" style="max-width:160px">
                    </td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-field">删</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p style="margin-top:10px">
            <button type="button" class="btn btn-muted" id="addField"><i class="fas fa-plus"></i> 加字段</button>
        </p>

        <div class="form-actions">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.forms.index') }}">返回</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var tbody = document.querySelector('#fieldsTable tbody');
    var idx = tbody.querySelectorAll('tr').length;
    document.getElementById('addField').addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.className = 'field-row';
        tr.innerHTML =
            '<td><input type="text" name="fields[' + idx + '][name]" placeholder="email" style="max-width:140px"></td>' +
            '<td><input type="text" name="fields[' + idx + '][label]" placeholder="邮箱" style="max-width:140px"></td>' +
            '<td><select name="fields[' + idx + '][type]" style="max-width:120px">' +
            ['text','email','tel','number','textarea','select','radio','checkbox'].map(function (t) {
                return '<option value="' + t + '">' + t + '</option>';
            }).join('') +
            '</select></td>' +
            '<td style="text-align:center"><input type="checkbox" name="fields[' + idx + '][required]" value="1"></td>' +
            '<td><input type="text" name="fields[' + idx + '][placeholder]" placeholder="占位提示" style="max-width:120px;margin-bottom:4px">' +
            '<input type="text" name="fields[' + idx + '][choices]" placeholder="选项A,选项B" style="max-width:160px"></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger remove-field">删</button></td>';
        tbody.appendChild(tr);
        idx++;
    });
    tbody.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-field')) {
            var rows = tbody.querySelectorAll('tr');
            if (rows.length <= 1) return;
            e.target.closest('tr').remove();
        }
    });
})();
</script>
@endpush
