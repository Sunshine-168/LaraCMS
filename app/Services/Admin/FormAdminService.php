<?php

namespace App\Services\Admin;

use App\Models\Cms\CmsForm;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class FormAdminService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return CmsForm::query()->withCount('submissions')->orderByDesc('id')->paginate($perPage);
    }

    public function save(?CmsForm $form, array $input): array
    {
        $form ??= new CmsForm;
        $slug = Str::slug((string) ($input['slug'] ?? ''), '-');
        if ($slug === '') {
            $slug = Str::slug((string) ($input['name'] ?? 'form'), '-') ?: 'form';
        }

        $exists = CmsForm::query()
            ->where('slug', $slug)
            ->when($form->exists, fn ($q) => $q->where('id', '!=', $form->id))
            ->exists();
        if ($exists) {
            return Result::fail('标识 slug 已存在');
        }

        $form->fill([
            'name' => $input['name'] ?? '未命名表单',
            'slug' => $slug,
            'is_active' => ! empty($input['is_active']),
            'notify_email' => trim((string) ($input['notify_email'] ?? '')) ?: null,
            'fields' => $this->normalizeFields($input['fields'] ?? []),
        ]);
        $form->save();

        return Result::ok($form, '表单已保存');
    }

    public function delete(CmsForm $form): array
    {
        $form->delete();

        return Result::ok(null, '已删除');
    }

    /** @param  mixed  $raw */
    protected function normalizeFields($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($row['name'] ?? '')) ?: ('field_'.$i);
            $label = trim((string) ($row['label'] ?? $name));
            if ($label === '' && empty($row['name'])) {
                continue;
            }
            $type = (string) ($row['type'] ?? 'text');
            if (! in_array($type, ['text', 'email', 'tel', 'number', 'textarea', 'select', 'radio', 'checkbox'], true)) {
                $type = 'text';
            }
            $choices = trim((string) ($row['choices'] ?? ''));
            $field = [
                'name' => $name,
                'label' => $label ?: $name,
                'type' => $type,
                'required' => ! empty($row['required']),
                'placeholder' => trim((string) ($row['placeholder'] ?? '')),
            ];
            if ($choices !== '' && in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $field['choices'] = array_values(array_filter(array_map('trim', explode(',', $choices))));
            }
            $out[] = $field;
        }

        return $out;
    }
}
