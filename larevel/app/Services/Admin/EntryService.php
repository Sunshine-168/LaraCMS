<?php

namespace App\Services\Admin;

use App\Models\Cms\Entry;
use App\Services\Cms\HtmlCacheService;
use App\Support\AdminCategoryScope;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EntryService
{
    public function __construct(private readonly HtmlCacheService $htmlCache)
    {
    }

    public function paginate(?int $modelId = null, int $perPage = 15): LengthAwarePaginator
    {
        $q = Entry::query()->with(['category', 'modelDef'])->orderByDesc('id');
        if ($modelId) {
            $q->where('model_id', $modelId);
        }
        AdminCategoryScope::applyToEntries($q);

        return $q->paginate($perPage);
    }

    public function paginateTrashed(int $perPage = 20): LengthAwarePaginator
    {
        $q = Entry::onlyTrashed()->with(['category', 'modelDef'])->orderByDesc('deleted_at');
        AdminCategoryScope::applyToEntries($q);

        return $q->paginate($perPage);
    }

    public function save(?Entry $entry, array $input): array
    {
        if ($entry?->exists && ! AdminCategoryScope::canAccess($entry->category_id ? (int) $entry->category_id : null)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }

        $categoryId = $input['category_id'] ?: null;
        if ($categoryId && ! AdminCategoryScope::canAccess((int) $categoryId)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }
        if (AdminCategoryScope::allowedIds() !== null && ! $categoryId) {
            return Result::fail('请选择有权限的栏目');
        }

        $entry ??= new Entry;
        $packed = Translatable::packTranslations($input['locales'] ?? [], [
            'title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description',
        ]);
        $entry->fill($packed);
        $entry->category_id = $categoryId;
        $entry->model_id = (int) $input['model_id'];
        $entry->cover = $input['cover'] ?? null;
        $entry->flags = $this->normalizeFlags($input);
        $entry->status = $input['status'] ?? 'draft';
        $entry->sort_order = (int) ($input['sort_order'] ?? 0);
        $entry->is_homepage = ! empty($input['is_homepage']);
        $entry->attrs = $input['attrs'] ?? [];
        if (($input['status'] ?? '') === 'published') {
            $entry->published_at = ! empty($input['published_at'])
                ? $input['published_at']
                : ($entry->published_at ?: now());
        } else {
            $entry->published_at = ! empty($input['published_at']) ? $input['published_at'] : null;
        }

        if ($entry->is_homepage) {
            Entry::query()->where('id', '!=', $entry->id ?? 0)->update(['is_homepage' => false]);
        }

        $entry->rebuildSearchText();
        $entry->save();

        if (! empty($input['tag_ids']) && is_array($input['tag_ids'])) {
            $entry->tags()->sync(array_filter(array_map('intval', $input['tag_ids'])));
        }

        $this->htmlCache->forgetByEntry($entry);

        return Result::ok($entry, '保存成功');
    }

    /** @param  array<string, mixed>  $input */
    protected function normalizeFlags(array $input): ?string
    {
        $flags = [];
        if (isset($input['flag_list']) && is_array($input['flag_list'])) {
            $flags = array_merge($flags, array_map('trim', $input['flag_list']));
        }
        $raw = trim((string) ($input['flags'] ?? ''));
        if ($raw !== '') {
            $flags = array_merge($flags, array_map('trim', explode(',', $raw)));
        }
        $flags = array_values(array_unique(array_filter($flags)));

        return $flags === [] ? null : implode(',', $flags);
    }

    public function assertAccess(Entry $entry): ?string
    {
        if (! AdminCategoryScope::canAccess($entry->category_id ? (int) $entry->category_id : null)) {
            return AdminCategoryScope::denyMessage();
        }

        return null;
    }

    public function delete(Entry $entry): array
    {
        if ($msg = $this->assertAccess($entry)) {
            return Result::fail($msg);
        }
        $this->htmlCache->forgetByEntry($entry);
        $entry->delete();

        return Result::ok(null, '已移入回收站');
    }

    public function restore(Entry $entry): array
    {
        if ($msg = $this->assertAccess($entry)) {
            return Result::fail($msg);
        }
        $entry->restore();
        $this->htmlCache->forgetByEntry($entry);

        return Result::ok($entry, '已还原');
    }

    public function forceDelete(Entry $entry): array
    {
        if ($msg = $this->assertAccess($entry)) {
            return Result::fail($msg);
        }
        $this->htmlCache->forgetByEntry($entry);
        $entry->forceDelete();

        return Result::ok(null, '已彻底删除');
    }

    /** @param  list<int>  $ids */
    protected function scopedEntries(array $ids)
    {
        $q = Entry::query()->whereIn('id', $ids);
        AdminCategoryScope::applyToEntries($q);

        return $q;
    }

    /** @param  list<int>  $ids */
    public function batchStatus(array $ids, string $status): array
    {
        if (! in_array($status, ['draft', 'published'], true) || $ids === []) {
            return Result::fail('参数无效');
        }
        $this->scopedEntries($ids)->update([
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
        $this->htmlCache->forgetAll('entry:batch');

        return Result::ok(null, '批量更新完成');
    }

    /** @param  list<int>  $ids */
    public function batchDelete(array $ids): array
    {
        if ($ids === []) {
            return Result::fail('未选择内容');
        }
        $this->scopedEntries($ids)->get()->each(function (Entry $e) {
            $this->htmlCache->forgetByEntry($e);
            $e->delete();
        });

        return Result::ok(null, '已批量移入回收站');
    }

    /** @param  list<int>  $ids */
    public function batchMove(array $ids, int $categoryId): array
    {
        if ($ids === [] || $categoryId <= 0) {
            return Result::fail('请选择目标栏目');
        }
        if (! AdminCategoryScope::canAccess($categoryId)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }
        $this->scopedEntries($ids)->update(['category_id' => $categoryId]);
        $this->htmlCache->forgetAll('entry:batch-move');

        return Result::ok(null, '已批量移动栏目');
    }

    /** @param  list<int>  $ids */
    public function batchCopy(array $ids, ?int $categoryId = null): array
    {
        if ($ids === []) {
            return Result::fail('未选择内容');
        }
        if ($categoryId && ! AdminCategoryScope::canAccess($categoryId)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }
        $n = 0;
        $this->scopedEntries($ids)->with('tags')->get()->each(function (Entry $src) use ($categoryId, &$n) {
            $copy = $src->replicate(['views', 'downloads', 'clicks']);
            $copy->status = 'draft';
            $copy->published_at = null;
            $copy->is_homepage = false;
            if ($categoryId) {
                $copy->category_id = $categoryId;
            }
            $title = $copy->title ?? [];
            if (is_array($title)) {
                foreach ($title as $loc => $t) {
                    $title[$loc] = trim((string) $t).' (副本)';
                }
                $copy->title = $title;
            }
            $copy->save();
            $copy->tags()->sync($src->tags->pluck('id')->all());
            $n++;
        });
        $this->htmlCache->forgetAll('entry:batch-copy');

        return Result::ok(null, "已复制 {$n} 条为草稿");
    }

    /** 推送到推荐位（手工条目型） */
    public function batchPush(array $ids, int $recommendId): array
    {
        if ($ids === [] || $recommendId <= 0) {
            return Result::fail('请选择推荐位');
        }
        $recommend = \App\Models\Cms\Recommend::query()->find($recommendId);
        if (! $recommend) {
            return Result::fail('推荐位不存在');
        }
        $sort = (int) \App\Models\Cms\RecommendItem::query()->where('recommend_id', $recommendId)->max('sort_order');
        foreach ($this->scopedEntries($ids)->get() as $entry) {
            $sort++;
            \App\Models\Cms\RecommendItem::query()->updateOrCreate(
                ['recommend_id' => $recommendId, 'entry_id' => $entry->id],
                [
                    'title' => $entry->title,
                    'url' => null,
                    'image' => $entry->cover,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );
        }
        $this->htmlCache->forgetAll('entry:batch-push');

        return Result::ok(null, '已推送到推荐位');
    }
}
