<?php

namespace App\Services\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Menu;
use App\Models\Cms\MenuItem;
use App\Models\Cms\Page;
use App\Models\Cms\Post;
use App\Support\Locale;
use Illuminate\Support\Collection;

class MenuQueryService
{
    /** 按位置取顶级菜单项（含解析后的 URL 与 children） */
    public function items(string $location = 'header'): Collection
    {
        $menu = Menu::query()->where('location', $location)->where('is_active', true)->first();
        if (! $menu) {
            return collect();
        }

        $all = $menu->items()->where('is_active', true)->get()->map(function (MenuItem $item) {
            $item->resolved_url = $this->resolveUrl($item);
            $item->setRelation('children', collect());

            return $item;
        });

        $byId = $all->keyBy('id');
        $roots = collect();
        foreach ($all as $item) {
            if ($item->parent_id && $byId->has($item->parent_id)) {
                $byId[$item->parent_id]->children->push($item);
            } else {
                $roots->push($item);
            }
        }

        return $roots;
    }

    protected function resolveUrl(MenuItem $item): string
    {
        $locale = Locale::current();
        $prefix = (! Locale::hideDefault() || $locale !== Locale::default())
            ? '/'.Locale::toPublic($locale)
            : '';

        return match ($item->type) {
            'category' => optional(Category::query()->active()->find($item->reference_id))->frontUrl() ?: '#',
            'entry' => (string) (optional(Entry::query()->published()->with(['category', 'modelDef'])->find($item->reference_id))->url ?: '#'),
            'page' => ($prefix ?: '').'/'.(optional(Page::find($item->reference_id))->t('slug') ?: '#'),
            'post' => ($prefix ?: '').'/posts/'.(optional(Post::find($item->reference_id))->t('slug') ?: '#'),
            default => $this->localizeUrl($item->url ?: '#', $prefix),
        };
    }

    /** 站内相对链接补上语言前缀 */
    protected function localizeUrl(string $url, string $prefix): string
    {
        if ($url === '#' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if ($url === '/') {
            return $prefix !== '' ? $prefix : '/';
        }
        if (str_starts_with($url, '/') && $prefix !== '') {
            return $prefix.$url;
        }

        return $url;
    }
}
