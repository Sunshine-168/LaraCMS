<?php

namespace App\Http\Controllers\Api;

use App\Models\Cms\Entry;
use App\Models\Cms\Tag;
use App\Support\UrlMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagApiController extends ApiController
{
    public function listPage(Request $request): JsonResponse
    {
        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $page = Tag::query()->withCount(['entries' => fn ($q) => $q->published()])
            ->orderByDesc('id')
            ->paginate($limit);

        return $this->ok([
            'list' => $page->getCollection()->map(fn (Tag $t) => $this->transform($t))->values(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'limit' => $page->perPage(),
        ]);
    }

    public function detail(string $slug): JsonResponse
    {
        $tag = Tag::query()->get()->first(fn (Tag $t) => $t->t('slug') === $slug);
        if (! $tag) {
            return $this->fail('标签不存在', 404);
        }

        $limit = max(1, min(100, (int) request()->query('limit', 20)));
        $entries = $tag->entries()->with(['category', 'modelDef', 'tags'])->published()
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate($limit);

        return $this->ok([
            'tag' => $this->transform($tag),
            'list' => $entries->getCollection()->map(fn (Entry $e) => [
                'id' => $e->id,
                'title' => $e->t('title'),
                'slug' => $e->t('slug'),
                'excerpt' => $e->t('excerpt'),
                'cover' => cms_media($e->cover),
                'url' => $e->url,
                'published_at' => optional($e->published_at)?->toIso8601String(),
            ])->values(),
            'total' => $entries->total(),
            'page' => $entries->currentPage(),
            'limit' => $entries->perPage(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function transform(Tag $t): array
    {
        $slug = (string) $t->t('slug');

        return [
            'id' => $t->id,
            'title' => $t->t('title'),
            'slug' => $slug,
            'url' => UrlMode::localePrefix().'/tag/'.$slug,
            'entries_count' => (int) ($t->entries_count ?? $t->entries()->published()->count()),
        ];
    }
}
