<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContentTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, Archive>|LengthAwarePaginator
     */
    public function get(array $options = []): Collection|LengthAwarePaginator
    {
        $num = max(1, (int) ($options['num'] ?? ($this->context->category()?->num ?? 10) ?: 10));
        $page = (bool) ($options['page'] ?? false);
        $order = (string) ($options['order'] ?? 'weigh desc,publish_time desc,id desc');
        $insub = (bool) ($options['insub'] ?? true);

        $query = Archive::query()
            ->with(['category', 'modelDef'])
            ->published();

        $this->applyCategoryFilter($query, $options, $insub);
        $this->applyIdsFilter($query, $options);
        $this->applyModelFilter($query, $options);
        $this->applyOrder($query, $order);

        if (! empty($options['where']) && is_string($options['where'])) {
            $query->whereRaw($options['where']);
        }

        if ($page) {
            $paginator = $query->paginate($num)->withQueryString();
            $this->context->setPaginator($paginator);

            return $paginator;
        }

        $this->context->setPaginator(null);

        return $query->limit($num)->get();
    }

    /**
     * @param  array<string, mixed>  $options
     */
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

        if ($ids === []) {
            return;
        }

        $query->where(function (Builder $inner) use ($ids): void {
            $inner->whereIn('category_id', $ids);

            foreach ($ids as $id) {
                $inner->orWhereRaw('find_in_set(?, category_ids)', [(string) $id]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
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

    /**
     * @param  array<string, mixed>  $options
     */
    private function applyModelFilter(Builder $query, array $options): void
    {
        if (! empty($options['model']) && is_numeric($options['model'])) {
            $query->where('model_id', (int) $options['model']);
        }
    }

    private function applyOrder(Builder $query, string $order): void
    {
        foreach (explode(',', $order) as $segment) {
            $parts = preg_split('/\s+/', trim($segment)) ?: [];
            $column = $parts[0] ?? null;
            $direction = strtolower($parts[1] ?? 'asc');

            if ($column && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
                $query->orderBy($column, $direction === 'desc' ? 'desc' : 'asc');
            }
        }
    }

    /**
     * @return list<int>
     */
    private function categoryIds(mixed $catid, bool $insub): array
    {
        if (is_array($catid)) {
            $roots = array_map('intval', $catid);
        } else {
            $roots = [(int) $catid];
        }

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
