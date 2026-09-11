<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\ModelField;
use Illuminate\Support\Collection;

class OrderTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /**
     * 输出可排序项（供主题渲染排序链接）
     *
     * @param  array<string, mixed>  $options
     */
    public function get(array $options = []): Collection
    {
        $current = (string) request('order', $options['current'] ?? 'published_at desc');
        $items = collect([
            ['value' => 'published_at desc', 'label' => '最新', 'column' => 'published_at'],
            ['value' => 'published_at asc', 'label' => '最早', 'column' => 'published_at'],
            ['value' => 'sort_order desc', 'label' => '权重', 'column' => 'sort_order'],
            ['value' => 'views desc', 'label' => '浏览最多', 'column' => 'views'],
            ['value' => 'downloads_count desc', 'label' => '下载最多', 'column' => 'downloads_count'],
        ]);

        $modelId = $options['model'] ?? $this->context->category()?->model_id;
        if ($modelId) {
            if (! is_numeric($modelId)) {
                $modelId = \App\Models\Cms\ContentModel::query()->where('slug', (string) $modelId)->value('id');
            }
            if ($modelId) {
                $fields = ModelField::query()
                    ->where('model_id', (int) $modelId)
                    ->where('is_list', true)
                    ->orderBy('sort_order')
                    ->get();
                foreach ($fields as $field) {
                    // attrs 排序用查询参数 order=attr:field desc（ContentTag 识别）
                    $items->push([
                        'value' => 'attr:'.$field->name.' desc',
                        'label' => $field->label,
                        'column' => 'attr:'.$field->name,
                    ]);
                }
            }
        }

        return $items->map(function (array $row) use ($current) {
            $row['current'] = $row['value'] === $current;
            $row['url'] = request()->fullUrlWithQuery(['order' => $row['value']]);

            return (object) $row;
        });
    }
}
