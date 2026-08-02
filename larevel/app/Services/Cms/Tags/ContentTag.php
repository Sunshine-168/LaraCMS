<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContentTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /** @param  array<string, mixed>  $options */
    public function get(array $options = []): Collection|LengthAwarePaginator
    {
        $num = max(1, (int) ($options['num'] ?? ($this->context->category()?->page_size ?? 10) ?: 10));
        $page = (bool) ($options['page'] ?? false);
        $order = (string) ($options['order'] ?? request('order') ?: 'sort_order desc,published_at desc,id desc');
        $insub = (bool) ($options['insub'] ?? true);

        $query = Entry::query()->with(['category', 'modelDef', 'tags'])->published();

        $this->applyCategoryFilter($query, $options, $insub);
        $this->applyIdsFilter($query, $options);
        $this->applyModelFilter($query, $options);
        $this->applyFlagFilter($query, $options);
        $this->applyAttrFilter($query, $options);
        $this->applyOrder($query, $order);

        if ($page) {
            $paginator = $query->paginate($num)->withQueryString();
            $this->context->setPaginator($paginator);

            return $paginator;
        }

        $this->context->setPaginator(null);

        return $query->limit($num)->get();
    }

    private function applyCategoryFilter(Builder $query, array $options, bool $insub): void
    {
        $catid = $options['catid'] ?? null;
        if ($catid === null || $catid === '') {
            $catid = $this->context->category()?->id;
        }
        if ($catid === null || $catid === '') {
            return;
        }

        $ids = $this->categoryIds($catid, $insub);
        if ($ids !== []) {
            $query->whereIn('category_id', $ids);
        }
    }

    private function applyIdsFilter(Builder $query, array $options): void
    {
        if (! empty($options['aid'])) {
            $query->where('id', (int) $options['aid']);
        }
        if (! empty($options['aids'])) {
            $aids = is_array($options['aids'])
                ? $options['aids']
                : (preg_split('/\s*,\s*/', (string) $options['aids']) ?: []);
            $query->whereIn('id', array_map('intval', $aids));
        }
    }

    private function applyModelFilter(Builder $query, array $options): void
    {
        if (empty($options['model'])) {
            return;
        }
        if (is_numeric($options['model'])) {
            $query->where('model_id', (int) $options['model']);

            return;
        }
        $model = ContentModel::query()->where('slug', (string) $options['model'])->first();
        if ($model) {
            $query->where('model_id', $model->id);
        }
    }

    private function applyFlagFilter(Builder $query, array $options): void
    {
        if (empty($options['flag'])) {
            return;
        }
        $flag = (string) $options['flag'];
        $query->where(function (Builder $q) use ($flag) {
            $q->where('flags', $flag)
                ->orWhere('flags', 'like', $flag.',%')
                ->orWhere('flags', 'like', '%,'.$flag.',%')
                ->orWhere('flags', 'like', '%,'.$flag);
        });
    }

    /** 简单 attrs JSON 筛选，如 filter[platform]=windows */
    private function applyAttrFilter(Builder $query, array $options): void
    {
        $filters = $options['filter'] ?? request()->query('filter', []);
        if (! is_array($filters)) {
            return;
        }
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $key)) {
                continue;
            }
            $query->where("attrs->{$key}", $value);
        }
    }

    private function applyOrder(Builder $query, string $order): void
    {
        $order = str_replace(['weigh', 'publish_time'], ['sort_order', 'published_at'], $order);
        $allowed = ['id', 'sort_order', 'published_at', 'views', 'downloads_count', 'clicks', 'created_at'];

        foreach (explode(',', $order) as $segment) {
            $parts = preg_split('/\s+/', trim($segment)) ?: [];
            $column = $parts[0] ?? null;
            $direction = strtolower($parts[1] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            if (! $column) {
                continue;
            }
            if (str_starts_with($column, 'attr:') && preg_match('/^attr:([A-Za-z_][A-Za-z0-9_]*)$/', $column, $m)) {
                $query->orderBy("attrs->{$m[1]}", $direction);
                continue;
            }
            if (in_array($column, $allowed, true)) {
                $query->orderBy($column, $direction);
            }
        }
    }

    /** @return list<int> */
    private function categoryIds(mixed $catid, bool $insub): array
    {
        $roots = is_array($catid) ? array_map('intval', $catid) : [(int) $catid];
        $roots = array_values(array_filter($roots));
        if ($roots === [] || ! $insub) {
            return $roots;
        }

        $all = Category::query()->active()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = collect($roots);
        foreach ($roots as $root) {
            $ids = $ids->merge($this->collectChildren($all, $root));
        }

        return $ids->unique()->values()->all();
    }

    private function collectChildren(Collection $grouped, int $parentId): Collection
    {
        $children = collect();
        foreach ($grouped->get($parentId, collect()) as $item) {
            $children->push((int) $item->id);
            $children = $children->merge($this->collectChildren($grouped, (int) $item->id));
        }

        return $children;
    }
}
