<?php

namespace App\Services\Admin;

use App\Models\Cms\Collector;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CollectorAdminService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Collector::query()->with(['modelDef', 'category'])->orderByDesc('id')->paginate($perPage);
    }

    public function save(?Collector $collector, array $input): array
    {
        $collector ??= new Collector;
        $collector->fill([
            'name' => $input['name'] ?? '未命名采集',
            'type' => $input['type'] ?? 'rss',
            'source_url' => $input['source_url'] ?? '',
            'model_id' => (int) ($input['model_id'] ?? 0),
            'category_id' => $input['category_id'] ?: null,
            'interval_minutes' => max(1, (int) ($input['interval_minutes'] ?? 60)),
            'limit_items' => max(1, (int) ($input['limit_items'] ?? 20)),
            'is_active' => ! empty($input['is_active']),
            'publish_immediately' => ! empty($input['publish_immediately']),
            'options' => [
                'list_path' => $input['list_path'] ?? 'items',
                'title_key' => $input['title_key'] ?? 'title',
                'link_key' => $input['link_key'] ?? 'url',
                'summary_key' => $input['summary_key'] ?? 'summary',
                'content_key' => $input['content_key'] ?? 'content',
                'guid_key' => $input['guid_key'] ?? 'id',
                'published_key' => $input['published_key'] ?? 'published_at',
                'cover_key' => $input['cover_key'] ?? 'cover',
            ],
        ]);
        $collector->save();

        return Result::ok($collector, '采集器已保存');
    }

    public function delete(Collector $collector): array
    {
        $collector->delete();

        return Result::ok(null, '已删除');
    }
}
