<?php

namespace App\Services\Admin;

use App\Models\Cms\Category;
use App\Services\Cms\HtmlCacheService;
use App\Support\AdminCategoryScope;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Support\Collection;

class CategoryService
{
    public function __construct(private readonly HtmlCacheService $htmlCache)
    {
    }

    public function all(): Collection
    {
        $q = Category::query()->with('modelDef')->orderBy('sort_order')->orderBy('id');
        AdminCategoryScope::applyToCategories($q);

        return $q->get();
    }

    public function save(?Category $category, array $input): array
    {
        if ($category?->exists && ! AdminCategoryScope::canAccess((int) $category->id)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }

        $category ??= new Category;
        // 有栏目授权时，不允许新建未授权栏目（新建后自动纳入授权需超管）
        if (! $category->exists && AdminCategoryScope::allowedIds() !== null) {
            return Result::fail('当前账号无权新建栏目，请联系超管');
        }

        $packed = Translatable::packTranslations($input['locales'] ?? [], [
            'title', 'slug', 'meta_title', 'meta_description',
        ]);
        $category->fill($packed);
        $category->type = $input['type'] ?? 'list';
        $category->model_id = $input['model_id'] ?: null;
        $category->parent_id = $input['parent_id'] ?: null;
        $category->list_tpl = $input['list_tpl'] ?? null;
        $category->show_tpl = $input['show_tpl'] ?? null;
        $category->category_tpl = $input['category_tpl'] ?? null;
        $category->is_menu = ! empty($input['is_menu']);
        $category->url = $input['url'] ?? null;
        $category->page_size = (int) ($input['page_size'] ?? 10);
        $category->sort_order = (int) ($input['sort_order'] ?? 0);
        $category->status = $input['status'] ?? 'published';
        $category->save();
        $this->htmlCache->forgetByCategory($category);

        return Result::ok($category, '保存成功');
    }

    public function delete(Category $category): array
    {
        if (! AdminCategoryScope::canAccess((int) $category->id)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }
        if ($category->entries()->exists() || $category->posts()->exists()) {
            return Result::fail('该栏目下仍有内容，无法删除');
        }
        if ($category->children()->exists()) {
            return Result::fail('请先删除子栏目');
        }
        $this->htmlCache->forgetByCategory($category);
        $category->delete();

        return Result::ok(null, '已删除');
    }
}
