<?php

namespace App\Services\Admin;

use App\Models\Cms\Post;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostService
{
    /** 分页列表 */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()->with('category')->orderByDesc('id')->paginate($perPage);
    }

    /** 保存文章 */
    public function save(?Post $post, array $input): array
    {
        $post ??= new Post;
        $packed = Translatable::packTranslations($input['locales'] ?? [], [
            'title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description',
        ]);

        $post->fill($packed);
        $post->category_id = $input['category_id'] ?: null;
        $post->cover = $input['cover'] ?? null;
        $post->status = $input['status'] ?? 'draft';
        $post->published_at = ($post->status === 'published')
            ? ($input['published_at'] ?? now())
            : null;
        $post->save();

        return Result::ok($post, '保存成功');
    }

    /** 删除文章 */
    public function delete(Post $post): array
    {
        $post->delete();

        return Result::ok(null, '已删除');
    }
}
