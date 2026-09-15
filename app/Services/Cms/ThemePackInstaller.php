<?php

namespace App\Services\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Services\Admin\ContentModelService;
use App\Support\Result;

/** 按主题包补模型/空栏目：没有才建，不改已有字段，不删内容。 */
class ThemePackInstaller
{
    public function __construct(
        private readonly ThemeCatalog $themes,
        private readonly ContentModelService $models,
    ) {}

    public function ensure(string $slug): array
    {
        if (! $this->themes->exists($slug)) {
            return Result::fail('没有这套主题');
        }

        $addedModels = [];
        foreach ($this->themes->packModelPayloads($slug) as $payload) {
            $modelSlug = (string) ($payload['model']['slug'] ?? '');
            if ($modelSlug === '') {
                continue;
            }
            $had = ContentModel::query()->where('slug', $modelSlug)->exists();
            $result = $this->models->importIfMissing($payload);
            if (! empty($result['ok']) && ! $had) {
                $addedModels[] = (string) ($payload['model']['name'] ?? $modelSlug);
            }
        }

        $addedCats = [];
        foreach ($this->themes->packCategories($slug) as $spec) {
            $catSlug = (string) ($spec['slug'] ?? '');
            $title = trim((string) ($spec['title'] ?? ''));
            $modelSlug = (string) ($spec['model'] ?? '');
            if ($catSlug === '' || $title === '' || $modelSlug === '') {
                continue;
            }
            if ($this->categoryBySlug($catSlug)) {
                continue;
            }
            $model = ContentModel::query()->where('slug', $modelSlug)->first();
            if (! $model) {
                continue;
            }
            $type = in_array(($spec['type'] ?? ''), ['list', 'single', 'hub', 'link'], true)
                ? $spec['type']
                : 'list';
            $max = (int) Category::query()->max('sort_order');
            Category::query()->create([
                'title' => ['zh_cn' => $title, 'en' => $title],
                'slug' => ['zh_cn' => $catSlug, 'en' => $catSlug],
                'type' => $type,
                'model_id' => $model->id,
                'list_tpl' => $spec['list_tpl'] ?? null,
                'show_tpl' => $spec['show_tpl'] ?? null,
                'is_menu' => ! empty($spec['menu']),
                'page_size' => 10,
                'sort_order' => $max + 10,
                'status' => 'published',
            ]);
            $addedCats[] = $title;
        }

        $bits = [];
        if ($addedModels !== []) {
            $bits[] = '已补上模型：'.implode('、', $addedModels);
        }
        if ($addedCats !== []) {
            $bits[] = '已补上栏目：'.implode('、', $addedCats).'（还没有文章）';
        }

        return Result::ok([
            'slug' => $slug,
            'added_models' => $addedModels,
            'added_categories' => $addedCats,
        ], $bits === [] ? '' : implode('。', $bits));
    }

    protected function categoryBySlug(string $slug): ?Category
    {
        foreach (Category::query()->get() as $cat) {
            $bag = $cat->slug;
            if (! is_array($bag)) {
                continue;
            }
            if (in_array($slug, $bag, true)) {
                return $cat;
            }
        }

        return null;
    }
}
