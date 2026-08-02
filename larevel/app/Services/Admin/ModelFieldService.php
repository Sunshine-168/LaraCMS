<?php

namespace App\Services\Admin;

use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use App\Support\Result;

class ModelFieldService
{
    public function __construct(private readonly FieldBindService $fieldBind)
    {
    }

    public function save(ContentModel $model, ?ModelField $field, array $input): array
    {
        $field ??= new ModelField(['model_id' => $model->id]);
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($input['name'] ?? '')) ?: 'field';
        $choices = trim((string) ($input['choices'] ?? ''));
        $options = $field->options ?? [];
        if ($choices !== '') {
            $options['choices'] = array_values(array_filter(array_map('trim', explode(',', $choices))));
        }

        $field->fill([
            'model_id' => $model->id,
            'name' => $name,
            'label' => $input['label'] ?? $name,
            'type' => $input['type'] ?? 'text',
            'options' => $options,
            'is_required' => ! empty($input['is_required']),
            'is_filter' => ! empty($input['is_filter']),
            'is_list' => ! empty($input['is_list']),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ]);
        $field->save();

        if (! empty($input['bind_categories'])) {
            $this->fieldBind->saveBinds($field, array_values((array) ($input['category_ids'] ?? [])));
        }

        return Result::ok($field, '字段已保存');
    }

    public function delete(ModelField $field): array
    {
        $field->delete();

        return Result::ok(null, '已删除');
    }
}
