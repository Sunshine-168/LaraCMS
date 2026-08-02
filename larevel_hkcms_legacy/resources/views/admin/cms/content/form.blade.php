@extends('admin.layout')

@php
    $currentValue = function ($fieldName, $default = '') use ($row, $extra) {
        if ($row && isset($row->{$fieldName})) {
            return $row->{$fieldName};
        }
        if (array_key_exists($fieldName, $extra)) {
            return $extra[$fieldName];
        }
        return old($fieldName, $default);
    };
@endphp

@section('content')
    <section class="card">
        <form method="post" action="{{ $row ? route('admin.contents.update', $row->id) : route('admin.contents.store') }}">
            @csrf
            @if($row)
                @method('PUT')
            @endif

            <input type="hidden" name="model_id" value="{{ $selectedModel->id }}">

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 18px;">
                <div>
                    <label>所属栏目</label>
                    <select name="category_id" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        @foreach($categories->where('model_id', $selectedModel->id) as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $selectedCategory->id) === (int) $category->id)>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>样式</label>
                    <input name="style" value="{{ old('style', $row?->style ?? '') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                </div>
            </div>

            <div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                @foreach($fieldDefinitions as $field)
                    @php
                        $value = $currentValue($field->field_name, $field->default_value);
                        $options = $field->data_list ? json_decode($field->data_list, true) : [];
                        $type = $field->form_type;
                        $selectedValues = is_array($value) ? $value : (is_string($value) && str_contains($value, ',') ? explode(',', $value) : [$value]);
                    @endphp
                    <div style="{{ $type === 'editor' || $type === 'textarea' ? 'grid-column: 1 / -1;' : '' }}">
                        <label>{{ $field->field_title ?: $field->field_name }}</label>

                        @if($type === 'editor')
                            <x-addons.simditor :name="$field->field_name" :id="'editor_'.$field->field_name" :value="(string) $value" />
                        @elseif($type === 'textarea')
                            <textarea name="{{ $field->field_name }}" style="width:100%;min-height:120px;padding:10px;border:1px solid #d1d5db;border-radius:8px;">{{ $value }}</textarea>
                        @elseif($type === 'radio' && is_array($options) && $options !== [])
                            <div style="display:flex;gap:14px;align-items:center;min-height:42px;">
                                @foreach($options as $optionValue => $optionLabel)
                                    <label style="margin:0;">
                                        <input type="radio" name="{{ $field->field_name }}" value="{{ $optionValue }}" @checked((string) $value === (string) $optionValue)>
                                        {{ $optionLabel }}
                                    </label>
                                @endforeach
                            </div>
                        @elseif(in_array($type, ['select', 'selects'], true) && is_array($options) && $options !== [])
                            <select name="{{ $field->field_name }}{{ $type === 'selects' ? '[]' : '' }}" {{ $type === 'selects' ? 'multiple' : '' }} style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                                @foreach($options as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', $selectedValues), true))>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif(in_array($type, ['number', 'datetime'], true))
                            <input
                                name="{{ $field->field_name }}"
                                type="{{ $type === 'number' ? 'number' : 'datetime-local' }}"
                                value="{{ $type === 'datetime' && $value ? date('Y-m-d\TH:i', is_numeric($value) ? (int) $value : strtotime((string) $value)) : $value }}"
                                style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;"
                            >
                        @else
                            <input name="{{ $field->field_name }}" value="{{ is_array($value) ? implode(',', $value) : $value }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                        @endif

                        @if($field->tips)
                            <div class="meta" style="margin-top:6px;">{{ $field->tips }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn">{{ $row ? '保存内容' : '创建内容' }}</button>
                <a href="{{ route('admin.contents.index', ['category_id' => $selectedCategory->id, 'model_id' => $selectedModel->id]) }}" class="btn btn-secondary">返回列表</a>
            </div>
        </form>
    </section>
@endsection
