<?php

namespace App\Services\Admin;

use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Services\Cms\HtmlCacheService;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecommendService
{
    public function __construct(private readonly HtmlCacheService $htmlCache) {}

    public function all(): Collection
    {
        return Recommend::query()->withCount('items')->orderBy('id')->get();
    }

    public function save(?Recommend $recommend, array $input): array
    {
        return DB::transaction(function () use ($recommend, $input) {
            $recommend ??= new Recommend;
            $title = trim((string) ($input['title'] ?? ''));
            $name = strtolower(trim((string) ($input['name'] ?? '')));
            if ($name === '' && $title !== '') {
                $name = Str::slug($title);
            }
            if ($name === '') {
                return Result::fail('请填写标识码，如 home_banner');
            }
            if ($title === '') {
                $title = $name;
            }
            $dup = Recommend::query()
                ->where('name', $name)
                ->when($recommend->exists, fn ($q) => $q->where('id', '!=', $recommend->id))
                ->exists();
            if ($dup) {
                return Result::fail('标识码「'.$name.'」已存在');
            }
            $recommend->name = $name;
            $recommend->title = $title;
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
                    if (
                        ! Translatable::keyedLocalesHaveText($row, 'title')
                        && empty($row['image'])
                        && empty($row['url'])
                        && empty($row['html'])
                        && empty($row['entry_id'])
                    ) {
                        continue;
                    }
                    RecommendItem::query()->create([
                        'recommend_id' => $recommend->id,
                        'title' => Translatable::packKeyedLocales($row, 'title'),
                        'notes' => Translatable::packKeyedLocales($row, 'notes', false),
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

            return Result::ok($recommend->load('items'), '已保存');
        });
    }

    public function toggle(Recommend $recommend): array
    {
        $recommend->is_active = ! $recommend->is_active;
        $recommend->save();
        $this->htmlCache->forgetAll('recommend:'.$recommend->id);

        return Result::ok($recommend, $recommend->is_active ? '已启用，前台会再显示' : '已停用，前台不再调用');
    }

    public function delete(Recommend $recommend): array
    {
        $n = $recommend->items()->count();
        $this->htmlCache->forgetAll('recommend:'.$recommend->id);
        $recommend->delete();

        return Result::ok(null, $n > 0 ? '已删除推荐位和 '.$n.' 条条目' : '已删除');
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
