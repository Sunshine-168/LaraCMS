<?php

namespace App\Services\Admin;

use App\Models\Cms\ContentModel;
use App\Support\Result;

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
}
