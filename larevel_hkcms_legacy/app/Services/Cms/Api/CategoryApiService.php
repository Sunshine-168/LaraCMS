<?php

namespace App\Services\Cms\Api;

use App\Models\Cms\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryApiService
{
    public function listPage(array $filters, int $page, int $limit): array
    {
        $query = Category::query();

        $this->applyFilters($query, $filters);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->orderBy('weigh')
            ->orderByDesc('id')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'lists' => $paginator->getCollection()->map(fn (Category $category) => $this->serialize($category))->values()->all(),
            'count' => $paginator->total(),
        ];
    }

    public function detail(array $filters): ?array
    {
        $query = Category::query();
        $this->applyFilters($query, $filters);

        $category = $query->first();

        return $category ? $this->serialize($category) : null;
    }

    public function tree(array $filters): array
    {
        $items = Category::query()
            ->when(isset($filters['lang']) && $filters['lang'] !== '', fn ($query) => $query->where('lang', $filters['lang']))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('weigh')
            ->orderByDesc('id')
            ->get(['id', 'title', 'model_id', 'parent_id']);

        return $this->toTree($items, 0);
    }

    private function applyFilters($query, array $filters): void
    {
        $query
            ->when(isset($filters['model_id']) && is_numeric($filters['model_id']), fn ($q) => $q->where('model_id', (int) $filters['model_id']))
            ->when(isset($filters['parent_id']) && is_numeric($filters['parent_id']), fn ($q) => $q->where('parent_id', (int) $filters['parent_id']))
            ->when(isset($filters['type']) && $filters['type'] !== '', fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['name']) && $filters['name'] !== '', fn ($q) => $q->where('name', $filters['name']))
            ->when(isset($filters['title']) && $filters['title'] !== '', fn ($q) => $q->where('title', 'like', '%'.$filters['title'].'%'))
            ->when(isset($filters['ismenu']) && is_numeric($filters['ismenu']), fn ($q) => $q->where('ismenu', (int) $filters['ismenu']))
            ->when(isset($filters['user_auth']) && is_numeric($filters['user_auth']), fn ($q) => $q->where('user_auth', (int) $filters['user_auth']))
            ->when(isset($filters['lang']) && $filters['lang'] !== '', fn ($q) => $q->where('lang', $filters['lang']))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['id']) && is_numeric($filters['id']), fn ($q) => $q->where('id', (int) $filters['id']));
    }

    private function serialize(Category $category): array
    {
        $data = $category->toArray();
        $data['image'] = $this->absoluteUrl($data['image'] ?? '');

        return $data;
    }

    private function toTree(Collection $items, int $parentId): array
    {
        return $items
            ->filter(fn ($item) => (int) $item->parent_id === $parentId)
            ->map(function ($item) use ($items): array {
                $node = [
                    'value' => $item->id,
                    'label' => $item->title,
                ];

                if (isset($item->model_id)) {
                    $node['model_id'] = $item->model_id;
                }

                $children = $this->toTree($items, (int) $item->id);

                if ($children !== []) {
                    $node['children'] = $children;
                }

                return $node;
            })
            ->values()
            ->all();
    }

    private function absoluteUrl(?string $value): string
    {
        if (! $value) {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        return url($value);
    }
}
