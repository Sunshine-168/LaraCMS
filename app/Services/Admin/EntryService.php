<?php

namespace App\Services\Admin;

use App\Events\EntrySaved;
use App\Models\Cms\Entry;
use App\Models\Cms\EntryRevision;
use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Services\Cms\HtmlCacheService;
use App\Services\Cms\OpsNotifyService;
use App\Support\AdminCategoryScope;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class EntryService
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly OpsNotifyService $opsNotify,
    ) {}

    public function paginate(
        ?int $modelId = null,
        ?string $status = null,
        int $perPage = 15,
        ?string $keyword = null,
        ?int $categoryId = null,
        ?int $tagId = null,
        ?string $flag = null,
    ): LengthAwarePaginator {
        $query = Entry::query()
            ->with(['category', 'modelDef', 'member'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        $status = (string) $status;
        if ($status === 'scheduled') {
            $query->where('status', 'published')->where('published_at', '>', now());
        } elseif ($status === 'published') {
            $query->published();
        } elseif (in_array($status, ['draft', 'pending'], true)) {
            $query->where('status', $status);
        }
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($keyword, $like) {
                $inner->where('search_text', 'like', $like)
                    ->orWhere('title', 'like', $like);
                if (ctype_digit($keyword)) {
                    $inner->orWhere('id', (int) $keyword);
                }
            });
        }
        if ($categoryId) {
            $query->inCategories([$categoryId]);
        }
        if ($tagId) {
            $query->whereHas('tags', fn ($q) => $q->where('cms_tags.id', $tagId));
        }
        if ($flag) {
            $query->withFlag($flag);
        }
        AdminCategoryScope::applyToEntries($query);

        return $query->paginate($perPage)->withQueryString();
    }

    public function pendingCount(?int $modelId = null): int
    {
        $q = Entry::query()->where('status', 'pending');
        if ($modelId) {
            $q->where('model_id', $modelId);
        }
        AdminCategoryScope::applyToEntries($q);

        return $q->count();
    }

    public function paginateTrashed(int $perPage = 20, ?string $keyword = null, ?int $modelId = null): LengthAwarePaginator
    {
        $query = Entry::onlyTrashed()->with(['category', 'modelDef'])->orderByDesc('deleted_at')->orderByDesc('id');
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($keyword, $like) {
                $inner->where('search_text', 'like', $like)
                    ->orWhere('title', 'like', $like);
                if (ctype_digit($keyword)) {
                    $inner->orWhere('id', (int) $keyword);
                }
            });
        }
        AdminCategoryScope::applyToEntries($query);

        return $query->paginate($perPage)->withQueryString();
    }

    public function save(?Entry $entry, array $input): array
    {
        if ($entry?->exists) {
            $entry->loadMissing('categories');
            $existingIds = array_merge(
                $entry->category_id ? [(int) $entry->category_id] : [],
                $entry->categories->pluck('id')->map(fn ($id) => (int) $id)->all()
            );
            if ($existingIds !== [] && ! AdminCategoryScope::canAccessAny($existingIds)) {
                return Result::fail(AdminCategoryScope::denyMessage());
            }
        }

        $categoryId = $input['category_id'] ?: null;
        $extraIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['category_ids'] ?? [])))));
        if ($categoryId) {
            array_unshift($extraIds, (int) $categoryId);
            $extraIds = array_values(array_unique($extraIds));
        }
        if ($extraIds !== [] && ! AdminCategoryScope::canAccessAny($extraIds)) {
            return Result::fail(AdminCategoryScope::denyMessage());
        }
        if (AdminCategoryScope::allowedIds() !== null && ! $categoryId) {
            return Result::fail('请选择有权限的主栏目');
        }

        $entry ??= new Entry;
        $creating = ! $entry->exists;
        $wasPublished = $entry->exists && $entry->status === 'published';
        $wasPending = $entry->exists && $entry->status === 'pending';
        if ($entry->exists) {
            $entry->loadMissing(['categories', 'tags']);
            $this->snapshotRevision($entry);
        }
        $localeFields = ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description'];
        $packed = Translatable::packTranslations(
            $input['locales'] ?? [],
            $localeFields,
            Translatable::existingTranslations($entry, $localeFields),
        );
        $entry->fill($packed);
        $entry->category_id = $categoryId;
        $entry->model_id = (int) $input['model_id'];
        $entry->cover = $input['cover'] ?? null;
        $entry->flags = $this->normalizeFlags($input);
        $status = (string) ($input['status'] ?? 'draft');
        if ($status === 'published' && ! $this->canPublish() && ! $wasPublished) {
            $status = 'pending';
            $input['published_at'] = '';
        }
        if (! $this->canPublish() && $wasPublished) {
            $status = 'published';
        }
        $entry->status = $status;
        $entry->sort_order = (int) ($input['sort_order'] ?? 0);
        $entry->is_homepage = ! empty($input['is_homepage']);
        $entry->attrs = $input['attrs'] ?? [];
        if ($status === 'published') {
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
        $this->ensurePreviewToken($entry);

        $entry->categories()->sync($extraIds);

        if (! empty($input['tag_ids']) && is_array($input['tag_ids'])) {
            $entry->tags()->sync(array_filter(array_map('intval', $input['tag_ids'])));
        }

        $this->htmlCache->forgetByEntry($entry);
        if ($status === 'pending' && ! $wasPending) {
            $this->opsNotify->entryPending($entry->loadMissing('member'));
        }
        event(new EntrySaved($entry, $creating, $status === 'published' && ! $wasPublished));

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
        $entry->loadMissing('categories');
        $ids = array_merge(
            $entry->category_id ? [(int) $entry->category_id] : [],
            $entry->categories->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        if ($ids !== [] && ! AdminCategoryScope::canAccessAny($ids)) {
            return AdminCategoryScope::denyMessage();
        }
        if ($ids === [] && AdminCategoryScope::allowedIds() !== null) {
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

        return Result::ok($entry, '已还原，可在内容列表中继续编辑');
    }

    /** @param  list<int>  $ids */
    public function restoreMany(array $ids): array
    {
        if ($ids === []) {
            return Result::fail('请先勾选要还原的内容');
        }
        $query = Entry::onlyTrashed()->whereIn('id', $ids);
        AdminCategoryScope::applyToEntries($query);
        $count = 0;
        foreach ($query->get() as $entry) {
            $result = $this->restore($entry);
            if ($result['ok']) {
                $count++;
            }
        }

        return $count > 0
            ? Result::ok(null, '已还原 '.$count.' 篇，可在内容列表中继续编辑')
            : Result::fail('没有可还原的内容');
    }

    /** @param  list<int>  $ids */
    public function forceDeleteMany(array $ids): array
    {
        if ($ids === []) {
            return Result::fail('请先勾选要删除的内容');
        }
        $query = Entry::onlyTrashed()->whereIn('id', $ids);
        AdminCategoryScope::applyToEntries($query);
        $count = 0;
        foreach ($query->get() as $entry) {
            $result = $this->forceDelete($entry);
            if ($result['ok']) {
                $count++;
            }
        }

        return $count > 0
            ? Result::ok(null, '已彻底删除 '.$count.' 篇')
            : Result::fail('没有可删除的内容');
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

    public function emptyTrash(): array
    {
        $q = Entry::onlyTrashed();
        AdminCategoryScope::applyToEntries($q);
        $count = 0;
        $q->orderBy('id')->chunkById(50, function ($rows) use (&$count) {
            foreach ($rows as $entry) {
                $this->htmlCache->forgetByEntry($entry);
                $entry->forceDelete();
                $count++;
            }
        });

        return Result::ok(null, '已清空回收站（'.$count.'）');
    }

    /** @param  list<int>  $ids */
    protected function scopedEntries(array $ids)
    {
        $q = Entry::query()->whereIn('id', $ids);
        AdminCategoryScope::applyToEntries($q);

        return $q;
    }

    public function canPublish(): bool
    {
        $admin = auth('admin')->user();

        return $admin && $admin->canPerm('publish');
    }

    /** @param  list<int>  $ids */
    public function batchStatus(array $ids, string $status): array
    {
        if (! in_array($status, ['draft', 'pending', 'published'], true) || $ids === []) {
            return Result::fail('参数无效');
        }
        if ($status === 'published' && ! $this->canPublish()) {
            return Result::fail('无发布权限，请提交待审由超管发布');
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
        $this->scopedEntries($ids)->get()->each(function (Entry $e) use ($categoryId) {
            $e->category_id = $categoryId;
            $e->save();
            $e->categories()->sync([$categoryId]);
        });
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
        $this->scopedEntries($ids)->with(['tags', 'categories'])->get()->each(function (Entry $src) use ($categoryId, &$n) {
            $copy = $src->replicate(['views', 'downloads', 'clicks', 'preview_token', 'preview_expires_at', 'member_id']);
            $copy->member_id = null;
            $copy->status = 'draft';
            $copy->published_at = null;
            $copy->is_homepage = false;
            $copy->preview_token = null;
            $copy->preview_expires_at = null;
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
            $catIds = $categoryId
                ? [$categoryId]
                : array_values(array_unique(array_filter(array_merge(
                    $src->category_id ? [(int) $src->category_id] : [],
                    $src->categories->pluck('id')->map(fn ($id) => (int) $id)->all()
                ))));
            $copy->categories()->sync($catIds);
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
        $recommend = Recommend::query()->find($recommendId);
        if (! $recommend) {
            return Result::fail('推荐位不存在');
        }
        $sort = (int) RecommendItem::query()->where('recommend_id', $recommendId)->max('sort_order');
        foreach ($this->scopedEntries($ids)->get() as $entry) {
            $sort++;
            RecommendItem::query()->updateOrCreate(
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

    public function issuePreviewToken(Entry $entry, int $days = 7): array
    {
        if ($msg = $this->assertAccess($entry)) {
            return Result::fail($msg);
        }
        $entry->preview_token = Str::random(40);
        $entry->preview_expires_at = now()->addDays(max(1, $days));
        $entry->save();

        return Result::ok($entry, '预览链接已刷新');
    }

    public function restoreRevision(Entry $entry, EntryRevision $revision): array
    {
        if ($msg = $this->assertAccess($entry)) {
            return Result::fail($msg);
        }
        if ((int) $revision->entry_id !== (int) $entry->id) {
            return Result::fail('版本不属于该文档');
        }
        $entry->loadMissing(['categories', 'tags']);
        $wasPublished = $entry->status === 'published';
        $this->snapshotRevision($entry);

        $snap = $revision->snapshot ?? [];
        foreach (['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description', 'cover', 'flags', 'status', 'sort_order', 'is_homepage', 'attrs', 'category_id'] as $field) {
            if (array_key_exists($field, $snap)) {
                $entry->{$field} = $snap[$field];
            }
        }
        $entry->published_at = ! empty($snap['published_at']) ? $snap['published_at'] : null;
        if (! $this->canPublish() && $wasPublished) {
            $entry->status = 'published';
            $entry->published_at = $entry->published_at ?: ($snap['published_at'] ?? now());
        } elseif (($entry->status ?? '') === 'published' && ! $this->canPublish() && ! $wasPublished) {
            $entry->status = 'pending';
            $entry->published_at = null;
        }
        $entry->rebuildSearchText();
        $entry->save();

        if (isset($snap['category_ids']) && is_array($snap['category_ids'])) {
            $entry->categories()->sync(array_map('intval', $snap['category_ids']));
        }
        if (isset($snap['tag_ids']) && is_array($snap['tag_ids'])) {
            $entry->tags()->sync(array_map('intval', $snap['tag_ids']));
        }

        $this->htmlCache->forgetByEntry($entry);

        return Result::ok($entry, '已还原到所选版本（当前稿已另存为新版本）');
    }

    protected function ensurePreviewToken(Entry $entry): void
    {
        if ($entry->preview_token && $entry->preview_expires_at && $entry->preview_expires_at->isFuture()) {
            return;
        }
        $entry->preview_token = Str::random(40);
        $entry->preview_expires_at = now()->addDays(7);
        $entry->save();
    }

    protected function snapshotRevision(Entry $entry): void
    {
        $entry->loadMissing(['categories', 'tags']);
        EntryRevision::query()->create([
            'entry_id' => $entry->id,
            'site_id' => $entry->site_id,
            'admin_id' => auth('admin')->id(),
            'title_hint' => mb_substr((string) $entry->t('title'), 0, 80) ?: null,
            'snapshot' => [
                'title' => $entry->title,
                'slug' => $entry->slug,
                'excerpt' => $entry->excerpt,
                'content' => $entry->content,
                'meta_title' => $entry->meta_title,
                'meta_description' => $entry->meta_description,
                'cover' => $entry->cover,
                'flags' => $entry->flags,
                'status' => $entry->status,
                'sort_order' => $entry->sort_order,
                'is_homepage' => (bool) $entry->is_homepage,
                'published_at' => optional($entry->published_at)?->toIso8601String(),
                'attrs' => $entry->attrs ?? [],
                'category_id' => $entry->category_id,
                'category_ids' => $entry->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'tag_ids' => $entry->tags->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ],
        ]);

        $keepIds = EntryRevision::query()->where('entry_id', $entry->id)
            ->orderByDesc('id')->limit(20)->pluck('id');
        EntryRevision::query()->where('entry_id', $entry->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
