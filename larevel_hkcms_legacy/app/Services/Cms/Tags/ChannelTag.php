<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Category;
use Illuminate\Support\Collection;

class ChannelTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, Category>
     */
    public function get(array $options = []): Collection
    {
        $type = (string) ($options['type'] ?? 'top');
        $num = isset($options['num']) ? (int) $options['num'] : null;
        $modelId = isset($options['model']) ? (int) $options['model'] : null;
        $ismenu = array_key_exists('ismenu', $options) ? (int) $options['ismenu'] : null;
        $name = $options['name'] ?? null;

        $current = $this->context->category();
        $parentId = $this->resolveParentId($type, $name, $current);

        $query = Category::query()->active()->orderBy('weigh')->orderByDesc('id');

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        if ($ismenu !== null) {
            $query->where('ismenu', $ismenu);
        }

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        if (! empty($options['where']) && is_string($options['where'])) {
            $query->whereRaw($options['where']);
        }

        if ($num !== null && $num > 0) {
            $query->limit($num);
        }

        return $query->get();
    }

    private function resolveParentId(string $type, mixed $name, ?Category $current): ?int
    {
        if ($name !== null && $name !== '') {
            if (is_numeric($name)) {
                return (int) $name;
            }

            $named = Category::query()->active()->where('name', (string) $name)->first();

            return $named?->id;
        }

        return match ($type) {
            'top' => 0,
            'son', 'selfson' => $current?->id,
            'peer' => $current?->parent_id ?? 0,
            'selftop' => $this->topParentId($current),
            default => 0,
        };
    }

    private function topParentId(?Category $category): ?int
    {
        if (! $category) {
            return 0;
        }

        $cursor = $category;
        $guard = 0;

        while ($cursor && (int) $cursor->parent_id > 0 && $guard < 20) {
            $parent = Category::query()->active()->find((int) $cursor->parent_id);
            if (! $parent) {
                break;
            }
            $cursor = $parent;
            $guard++;
        }

        return $cursor?->id ?? 0;
    }
}
