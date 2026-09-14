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
    public function paginate(int $perPage = 30, ?string $keyword = null, bool $unusedOnly = false): LengthAwarePaginator
    {
        $query = Tag::query()->withCount('entries');
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }
        if ($unusedOnly) {
            $query->whereDoesntHave('entries');
        }
        $query->orderByDesc('entries_count')->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }

    public function save(?Tag $tag, array $input): array
    {
        $tag ??= new Tag;
        if (empty($input['locales']) && trim((string) ($input['title'] ?? '')) !== '') {
            $title = trim((string) $input['title']);
            $input['locales'] = [];
            foreach (Locale::codes() as $code) {
                $input['locales'][$code] = [
                    'title' => $title,
                    'slug' => (string) ($input['slug'] ?? ''),
                ];
            }
        }
        $packed = Translatable::packTranslations($input['locales'] ?? [], ['title', 'slug']);
        $fallbackTitle = '';
        foreach (array_merge([Locale::default()], Locale::codes()) as $code) {
            $t = trim((string) ($packed['title'][$code] ?? ''));
            if ($t !== '') {
                $fallbackTitle = $t;
                break;
            }
        }
        foreach (Locale::codes() as $code) {
            if (trim((string) ($packed['title'][$code] ?? '')) === '' && $fallbackTitle !== '') {
                $packed['title'][$code] = $fallbackTitle;
            }
        }

        $hasTitle = false;
        foreach (Locale::codes() as $code) {
            $title = trim((string) ($packed['title'][$code] ?? ''));
            $slug = trim((string) ($packed['slug'][$code] ?? ''));
            if ($title !== '') {
                $hasTitle = true;
            }
            if ($slug === '' && $title !== '') {
                $packed['slug'][$code] = Str::slug($title) ?: ('tag-'.substr(md5($title), 0, 6));
            }
        }
        if (! $hasTitle) {
            return Result::fail('请填写标签名称');
        }

        $tag->fill($packed);
        foreach (Locale::codes() as $code) {
            $slug = (string) ($packed['slug'][$code] ?? '');
            if ($slug === '') {
                continue;
            }
            $dup = Tag::query()
                ->when($tag->exists, fn ($q) => $q->where('id', '!=', $tag->id))
                ->where("slug->{$code}", $slug)
                ->exists();
            if ($dup) {
                return Result::fail('网址标识「'.$slug.'」已被使用');
            }
        }
        $tag->save();

        return Result::ok($tag, '标签已保存');
    }

    public function delete(Tag $tag): array
    {
        $count = $tag->entries()->count();
        $tag->entries()->detach();
        $tag->delete();

        return Result::ok(null, $count > 0 ? '已删除标签，并从 '.$count.' 篇内容上移除' : '已删除');
    }
}
