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
    public function __construct(private readonly HtmlCacheService $htmlCache) {}

    public function all(): Collection
    {
        $q = Category::query()->with('modelDef')->withCount('entries')->orderBy('sort_order')->orderBy('id');
        AdminCategoryScope::applyToCategories($q);

        return $q->get();
    }

    /** 按父子层级展开，depth 从 0 起 */
    public function flattenTree(): Collection
    {
        $all = $this->all();
        $byParent = $all->groupBy(fn (Category $c) => (int) ($c->parent_id ?? 0));
        $rows = collect();
        $placed = [];
        $append = function (Category $cat, int $depth) use (&$append, &$placed, &$rows, $byParent): void {
            $id = (int) $cat->id;
            if (isset($placed[$id])) {
                return;
            }
            $cat->setAttribute('depth', $depth);
            $rows->push($cat);
            $placed[$id] = true;
            foreach ($byParent->get($id, collect()) as $child) {
                $append($child, $depth + 1);
            }
        };
        foreach ($byParent->get(0, collect()) as $root) {
            $append($root, 0);
        }
        foreach ($all as $cat) {
            if (! isset($placed[(int) $cat->id])) {
                $append($cat, 0);
            }
        }

        return $rows;
    }

    public function parentsForSelect(?Category $current = null): Collection
    {
        $flat = $this->flattenTree();
        if (! $current?->exists) {
            return $flat;
        }
        $skipDepth = null;
        return $flat->filter(function (Category $row) use ($current, &$skipDepth) {
            if ($skipDepth !== null) {
                if ($row->depth > $skipDepth) {
                    return false;
                }
                $skipDepth = null;
            }
            if ((int) $row->id === (int) $current->id) {
                $skipDepth = (int) $row->depth;

                return false;
            }

            return true;
        })->values();
    }

    /** @return list<int> */
    public function descendantIds(int $id): array
    {
        $ids = [];
        $walk = function (int $pid) use (&$walk, &$ids): void {
            foreach (Category::query()->where('parent_id', $pid)->pluck('id') as $cid) {
                $cid = (int) $cid;
                $ids[] = $cid;
                $walk($cid);
            }
        };
        $walk($id);

        return $ids;
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
        $parentId = $input['parent_id'] ? (int) $input['parent_id'] : null;
        if ($parentId && $category->exists) {
            if ($parentId === (int) $category->id) {
                return Result::fail('不能选择自己作为上级栏目');
            }
            if (in_array($parentId, $this->descendantIds((int) $category->id), true)) {
                return Result::fail('不能把栏目放到自己的下级下面');
            }
        }
        if ($parentId && ! AdminCategoryScope::canAccess($parentId) && AdminCategoryScope::allowedIds() !== null) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }
        $category->parent_id = $parentId;
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
        if ($category->entries()->exists()) {
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
