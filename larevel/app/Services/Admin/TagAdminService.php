<?php

namespace App\Services\Admin;

use App\Models\Cms\Tag;
use App\Support\Locale;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TagAdminService
{
    public function paginate(int $perPage = 30): LengthAwarePaginator
    {
        return Tag::query()->withCount('entries')->orderByDesc('id')->paginate($perPage);
    }

    public function save(?Tag $tag, array $input): array
    {
        $tag ??= new Tag;
        $packed = Translatable::packTranslations($input['locales'] ?? [], ['title', 'slug']);

        // 自动补 slug
        foreach (Locale::codes() as $code) {
            $title = $packed['title'][$code] ?? '';
            $slug = $packed['slug'][$code] ?? '';
            if ($slug === '' && $title !== '') {
                $packed['slug'][$code] = Str::slug($title) ?: ('tag-'.substr(md5($title), 0, 6));
            }
        }

        $tag->fill($packed);
        $tag->save();

        return Result::ok($tag, '标签已保存');
    }

    public function delete(Tag $tag): array
    {
        $tag->entries()->detach();
        $tag->delete();

        return Result::ok(null, '已删除');
    }
}
