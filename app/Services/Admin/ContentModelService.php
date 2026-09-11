<?php

namespace App\Services\Admin;

use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use App\Support\Result;
use Illuminate\Support\Facades\DB;

class ContentModelService
{
    public function save(?ContentModel $model, array $input): array
    {
        $model ??= new ContentModel;
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($input['slug'] ?? ''))) ?: 'model';

        $exists = ContentModel::query()
            ->where('slug', $slug)
            ->when($model->exists, fn ($q) => $q->where('id', '!=', $model->id))
            ->exists();
        if ($exists) {
            return Result::fail('模型标识已存在');
        }

        $kind = (string) ($input['kind'] ?? 'list');
        if (! in_array($kind, ['list', 'single'], true)) {
            $kind = 'list';
        }

        $model->fill([
            'slug' => $slug,
            'name' => $input['name'] ?? $slug,
            'kind' => $kind,
            'list_tpl' => $input['list_tpl'] ?: ($kind === 'list' ? 'list.list' : null),
            'show_tpl' => $input['show_tpl'] ?: 'show.show',
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'is_active' => ! empty($input['is_active']),
            'is_searchable' => array_key_exists('is_searchable', $input)
                ? ! empty($input['is_searchable'])
                : ($model->exists ? (bool) $model->is_searchable : true),
        ]);
        $model->save();

        return Result::ok($model, '模型已保存');
    }

    public function delete(ContentModel $model): array
    {
        if ($model->entries()->exists()) {
            return Result::fail('该模型下仍有内容，无法删除');
        }
        if ($model->categories()->exists()) {
            return Result::fail('该模型仍被栏目使用，无法删除');
        }
        $model->fields()->delete();
        $model->delete();

        return Result::ok(null, '已删除');
    }

    /** 复制模型及其字段（不含内容） */
    public function copy(ContentModel $source, ?string $newSlug = null, ?string $newName = null): array
    {
        $source->loadMissing('fields');
        $baseSlug = $newSlug ?: ($source->slug.'_copy');
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower($baseSlug)) ?: 'model_copy';
        $i = 1;
        $try = $slug;
        while (ContentModel::query()->where('slug', $try)->exists()) {
            $try = $slug.'_'.$i++;
        }

        return DB::transaction(function () use ($source, $try, $newName) {
            $model = ContentModel::query()->create([
                'slug' => $try,
                'name' => $newName ?: ($source->name.' 副本'),
                'kind' => $source->kind,
                'list_tpl' => $source->list_tpl,
                'show_tpl' => $source->show_tpl,
                'sort_order' => (int) $source->sort_order,
                'is_active' => (bool) $source->is_active,
                'is_searchable' => (bool) ($source->is_searchable ?? true),
            ]);
            foreach ($source->fields as $field) {
                ModelField::query()->create([
                    'model_id' => $model->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'options' => $field->options,
                    'is_required' => $field->is_required,
                    'is_filter' => $field->is_filter,
                    'is_list' => $field->is_list,
                    'sort_order' => $field->sort_order,
                ]);
            }

            return Result::ok($model, '已复制为 '.$model->slug);
        });
    }

    /** @return array<string, mixed> */
    public function exportPayload(ContentModel $model): array
    {
        $model->loadMissing('fields');

        return [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'model' => [
                'slug' => $model->slug,
                'name' => $model->name,
                'kind' => $model->kind,
                'list_tpl' => $model->list_tpl,
                'show_tpl' => $model->show_tpl,
                'sort_order' => (int) $model->sort_order,
                'is_active' => (bool) $model->is_active,
                'is_searchable' => (bool) ($model->is_searchable ?? true),
            ],
            'fields' => $model->fields->map(fn (ModelField $f) => [
                'name' => $f->name,
                'label' => $f->label,
                'type' => $f->type,
                'options' => $f->options,
                'is_required' => (bool) $f->is_required,
                'is_filter' => (bool) $f->is_filter,
                'is_list' => (bool) $f->is_list,
                'sort_order' => (int) $f->sort_order,
            ])->values()->all(),
        ];
    }

    /** 从导出 JSON 导入（同 slug 则覆盖字段定义，不删内容） */
    public function importPayload(array $payload, bool $overwrite = true): array
    {
        $meta = $payload['model'] ?? null;
        if (! is_array($meta) || empty($meta['slug']) || empty($meta['name'])) {
            return Result::fail('无效的模型导出文件');
        }
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $meta['slug'])) ?: '';
        if ($slug === '') {
            return Result::fail('模型标识无效');
        }

        return DB::transaction(function () use ($meta, $payload, $slug, $overwrite) {
            $model = ContentModel::query()->where('slug', $slug)->first();
            if ($model && ! $overwrite) {
                return Result::fail('模型 '.$slug.' 已存在');
            }
            if (! $model) {
                $model = new ContentModel(['slug' => $slug]);
            }
            $kind = in_array(($meta['kind'] ?? ''), ['list', 'single'], true) ? $meta['kind'] : 'list';
            $model->fill([
                'name' => $meta['name'],
                'kind' => $kind,
                'list_tpl' => $meta['list_tpl'] ?? ($kind === 'list' ? 'list.list' : null),
                'show_tpl' => $meta['show_tpl'] ?? 'show.show',
                'sort_order' => (int) ($meta['sort_order'] ?? 0),
                'is_active' => ! empty($meta['is_active']),
                'is_searchable' => array_key_exists('is_searchable', $meta) ? ! empty($meta['is_searchable']) : true,
            ]);
            $model->save();

            $fields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
            ModelField::query()->where('model_id', $model->id)->delete();
            foreach ($fields as $i => $field) {
                if (! is_array($field) || empty($field['name'])) {
                    continue;
                }
                ModelField::query()->create([
                    'model_id' => $model->id,
                    'name' => preg_replace('/[^a-z0-9_]/', '', strtolower((string) $field['name'])) ?: ('field_'.$i),
                    'label' => (string) ($field['label'] ?? $field['name']),
                    'type' => (string) ($field['type'] ?? 'text'),
                    'options' => $field['options'] ?? null,
                    'is_required' => ! empty($field['is_required']),
                    'is_filter' => ! empty($field['is_filter']),
                    'is_list' => ! empty($field['is_list']),
                    'sort_order' => (int) ($field['sort_order'] ?? $i),
                ]);
            }

            return Result::ok($model, '已导入模型 '.$model->slug.'（'.count($fields).' 个字段）');
        });
    }
}
