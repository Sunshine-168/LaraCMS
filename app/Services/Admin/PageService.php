<?php

namespace App\Services\Admin;

use App\Models\Cms\Page;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PageService
{
    /** 分页列表 */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Page::query()->orderByDesc('id')->paginate($perPage);
    }

    /** 保存页面（新建或更新） */
    public function save(?Page $page, array $input): array
    {
        $page ??= new Page;
        $packed = Translatable::packTranslations($input['locales'] ?? [], [
            'title', 'slug', 'content', 'meta_title', 'meta_description',
        ]);

        $page->fill($packed);
        $page->parent_id = $input['parent_id'] ?: null;
        $page->status = $input['status'] ?? 'draft';
        $page->sort_order = (int) ($input['sort_order'] ?? 0);
        $page->is_homepage = ! empty($input['is_homepage']);
        $page->published_at = ($page->status === 'published')
            ? ($input['published_at'] ?? now())
            : null;

        if ($page->is_homepage) {
            Page::query()->where('id', '!=', $page->id ?? 0)->update(['is_homepage' => false]);
        }

        $page->save();

        return Result::ok($page, '保存成功');
    }

    /** 删除页面 */
    public function delete(Page $page): array
    {
        $page->delete();

        return Result::ok(null, '已删除');
    }
}
