<?php

namespace App\Services\Cms\Tags;

use App\Models\Cms\Entry;
use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Support\Cdn;
use Illuminate\Support\Collection;

class AdvTag
{
    /** @param  array<string, mixed>  $options */
    public function get(array $options = []): Collection
    {
        $name = (string) ($options['name'] ?? '');
        if ($name === '') {
            return collect();
        }

        $recommend = Recommend::query()
            ->where('name', $name)
            ->where('is_active', true)
            ->with(['items' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->first();

        if (! $recommend) {
            return collect();
        }

        if ($recommend->type === 'query') {
            $items = $this->queryItems($recommend, $options);
        } else {
            $items = $recommend->items;
            if (! empty($options['num'])) {
                $items = $items->take((int) $options['num']);
            }
            if (! empty($options['itemid'])) {
                $items = $items->slice(((int) $options['itemid']) - 1, 1)->values();
            }
        }

        return $items->values()->map(function ($item) {
            if ($item instanceof RecommendItem) {
                $item->image = Cdn::url($item->image);
            }

            return $item;
        });
    }

    /** @param  array<string, mixed>  $options */
    protected function queryItems(Recommend $recommend, array $options): Collection
    {
        $p = $recommend->query_params ?? [];
        $limit = (int) ($options['num'] ?? $p['limit'] ?? 10);
        $limit = max(1, min(100, $limit));

        $q = Entry::query()->with(['category', 'modelDef'])->published();

        if (! empty($p['model_id'])) {
            $q->where('model_id', (int) $p['model_id']);
        }
        if (! empty($p['category_ids']) && is_array($p['category_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $p['category_ids'])));
            if ($ids !== []) {
                $q->inCategories($ids);
            }
        }
        if (! empty($p['flag'])) {
            $flag = trim((string) $p['flag']);
            $q->where(function ($w) use ($flag) {
                $w->where('flags', $flag)
                    ->orWhere('flags', 'like', $flag.',%')
                    ->orWhere('flags', 'like', '%,'.$flag.',%')
                    ->orWhere('flags', 'like', '%,'.$flag);
            });
        }

        $order = (string) ($p['order'] ?? 'published_at_desc');
        match ($order) {
            'published_at_asc' => $q->orderBy('published_at'),
            'sort_asc' => $q->orderBy('sort_order')->orderByDesc('id'),
            'sort_desc' => $q->orderByDesc('sort_order')->orderByDesc('id'),
            'id_desc' => $q->orderByDesc('id'),
            'views_desc' => $q->orderByDesc('views'),
            default => $q->orderByDesc('published_at')->orderByDesc('id'),
        };

        return $q->limit($limit)->get()->map(function (Entry $entry) {
            $item = new RecommendItem([
                'title' => $entry->title,
                'notes' => $entry->excerpt,
                'image' => Cdn::url($entry->cover),
                'url' => $entry->url,
                'entry_id' => $entry->id,
                'is_active' => true,
            ]);
            $item->setRelation('entry', $entry);

            return $item;
        });
    }
}
