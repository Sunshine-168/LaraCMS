<?php

namespace App\Services\Admin;

use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Services\Cms\HtmlCacheService;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendService
{
    public function __construct(private readonly HtmlCacheService $htmlCache)
    {
    }

    public function all(): Collection
    {
        return Recommend::query()->with('items')->orderBy('id')->get();
    }

    public function save(?Recommend $recommend, array $input): array
    {
        return DB::transaction(function () use ($recommend, $input) {
            $recommend ??= new Recommend;
            $recommend->name = $input['name'] ?? 'block';
            $recommend->title = $input['title'] ?? $recommend->name;
            $type = (string) ($input['type'] ?? 'image');
            if (! in_array($type, ['image', 'link', 'html', 'entry', 'query'], true)) {
                $type = 'image';
            }
            $recommend->type = $type;
            $recommend->is_active = ! empty($input['is_active']);
            $recommend->query_params = $type === 'query' ? $this->normalizeQuery($input['query'] ?? []) : null;
            $recommend->save();

            RecommendItem::query()->where('recommend_id', $recommend->id)->delete();
            if ($type !== 'query') {
                foreach ($input['items'] ?? [] as $i => $row) {
                    if (empty($row['title_zh_cn']) && empty($row['image']) && empty($row['url']) && empty($row['html']) && empty($row['entry_id'])) {
                        continue;
                    }
                    RecommendItem::query()->create([
                        'recommend_id' => $recommend->id,
                        'title' => [
                            'zh_cn' => $row['title_zh_cn'] ?? '',
                            'en' => $row['title_en'] ?? ($row['title_zh_cn'] ?? ''),
                        ],
                        'notes' => [
                            'zh_cn' => $row['notes_zh_cn'] ?? '',
                            'en' => $row['notes_en'] ?? '',
                        ],
                        'image' => $row['image'] ?? null,
                        'url' => $row['url'] ?? null,
                        'html' => $row['html'] ?? null,
                        'entry_id' => $row['entry_id'] ?: null,
                        'sort_order' => $i,
                        'is_active' => true,
                    ]);
                }
            }

            $this->htmlCache->forgetAll('recommend:'.$recommend->id);

            return Result::ok($recommend->load('items'), '保存成功');
        });
    }

    public function delete(Recommend $recommend): array
    {
        $this->htmlCache->forgetAll('recommend:'.$recommend->id);
        $recommend->delete();

        return Result::ok(null, '已删除');
    }

    /** @param  array<string, mixed>  $input */
    protected function normalizeQuery(array $input): array
    {
        $cats = array_values(array_filter(array_map('intval', (array) ($input['category_ids'] ?? []))));

        return [
            'model_id' => ! empty($input['model_id']) ? (int) $input['model_id'] : null,
            'category_ids' => $cats,
            'flag' => trim((string) ($input['flag'] ?? '')) ?: null,
            'limit' => max(1, min(100, (int) ($input['limit'] ?? 10))),
            'order' => in_array($input['order'] ?? '', [
                'published_at_desc', 'published_at_asc', 'sort_asc', 'sort_desc', 'id_desc', 'views_desc',
            ], true) ? $input['order'] : 'published_at_desc',
        ];
    }
}
