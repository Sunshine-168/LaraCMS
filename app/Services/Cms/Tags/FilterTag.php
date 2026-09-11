<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use Illuminate\Support\Collection;

class FilterTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /** @param  array<string, mixed>  $options */
    public function get(array $options = []): Collection
    {
        $modelId = $options['model'] ?? $this->context->category()?->model_id;
        if ($modelId && ! is_numeric($modelId)) {
            $modelId = ContentModel::query()->where('slug', (string) $modelId)->value('id');
        }
        if (! $modelId) {
            return collect();
        }

        $fields = ModelField::query()
            ->where('model_id', (int) $modelId)
            ->where('is_filter', true)
            ->orderBy('sort_order')
            ->get();

        $current = request()->query('filter', []);

        return $fields->map(function (ModelField $field) use ($current) {
            $opts = $field->options['choices'] ?? $field->options ?? [];
            if (! is_array($opts)) {
                $opts = [];
            }
            $field->setAttribute('current', $current[$field->name] ?? null);
            $field->setAttribute('choices', $opts);

            return $field;
        });
    }
}
