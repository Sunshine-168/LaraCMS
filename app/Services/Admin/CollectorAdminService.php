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

    /** @return array<string, mixed> */
    public function attributesFromInput(array $input): array
    {
        return [
            'name' => $input['name'] ?? '未命名采集',
            'type' => $input['type'] ?? 'html',
            'source_url' => $input['source_url'] ?? '',
            'model_id' => (int) ($input['model_id'] ?? 0),
            'category_id' => ! empty($input['category_id']) ? $input['category_id'] : null,
            'interval_minutes' => max(1, (int) ($input['interval_minutes'] ?? 60)),
            'limit_items' => max(1, min(100, (int) ($input['limit_items'] ?? 10))),
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
                'item_selector' => trim((string) ($input['item_selector'] ?? '')),
                'link_selector' => trim((string) ($input['link_selector'] ?? 'a')),
                'title_selector' => trim((string) ($input['title_selector'] ?? '')),
                'summary_selector' => trim((string) ($input['summary_selector'] ?? '')),
                'cover_selector' => trim((string) ($input['cover_selector'] ?? '')),
                'detail_content_selector' => trim((string) ($input['detail_content_selector'] ?? '')),
                'page_count' => max(1, min(10, (int) ($input['page_count'] ?? 1))),
                'page_url' => trim((string) ($input['page_url'] ?? '')),
                'next_selector' => trim((string) ($input['next_selector'] ?? '')),
            ],
        ];
    }

    public function save(?Collector $collector, array $input): array
    {
        $collector ??= new Collector;
        $collector->fill($this->attributesFromInput($input));
        $collector->save();

        return Result::ok($collector, '采集源已保存');
    }

    public function toggle(Collector $collector): array
    {
        $collector->is_active = ! $collector->is_active;
        $collector->save();

        return Result::ok(
            $collector,
            $collector->is_active ? '已启用，到点会自动采集' : '已停用，不再自动采集'
        );
    }

    public function delete(Collector $collector): array
    {
        $collector->delete();

        return Result::ok(null, '已删除');
    }
}
