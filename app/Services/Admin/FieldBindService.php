<?php

namespace App\Services\Admin;

use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use App\Support\Result;
use Illuminate\Support\Collection;

class FieldBindService
{
    /** 未绑定任何栏目 = 全栏目可用；绑定后仅出现在所选栏目 */
    public function resolveFields(ContentModel $model, ?int $categoryId): Collection
    {
        $model->loadMissing(['fields.categories']);

        return $model->fields->filter(function (ModelField $field) use ($categoryId) {
            $bound = $field->categories;
            if ($bound->isEmpty()) {
                return true;
            }
            if (! $categoryId) {
                return false;
            }

            return $bound->contains('id', $categoryId);
        })->values();
    }

    /** @param  list<int>  $categoryIds */
    public function saveBinds(ModelField $field, array $categoryIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        // 仅允许该字段所属模型下的栏目
        if ($ids !== []) {
            $allowed = Category::query()
                ->where('model_id', $field->model_id)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();
            $ids = array_map('intval', $allowed);
        }
        $field->categories()->sync($ids);

        return Result::ok($field, $ids === [] ? '已清除栏目绑定（全栏目显示）' : '已绑定 '.count($ids).' 个栏目');
    }

    public function categoriesForModel(int $modelId): Collection
    {
        return Category::query()->where('model_id', $modelId)->orderBy('sort_order')->orderBy('id')->get();
    }
}
