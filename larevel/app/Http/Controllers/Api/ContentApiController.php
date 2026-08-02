<?php

namespace App\Http\Controllers\Api;
use App\Models\Cms\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentApiController extends ApiController
{
    public function listPage(Request $request): JsonResponse
    {
        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $q = Entry::query()->with(['category', 'modelDef', 'tags'])->published()->orderByDesc('published_at')->orderByDesc('id');

        if ($request->filled('category_id')) {
            $q->inCategories([(int) $request->query('category_id')]);
        }
        if ($request->filled('model_id')) {
            $q->where('model_id', (int) $request->query('model_id'));
        }
        if ($request->filled('flag')) {
            $flag = trim((string) $request->query('flag'));
            $q->where(function ($w) use ($flag) {
                $w->where('flags', $flag)
                    ->orWhere('flags', 'like', $flag.',%')
                    ->orWhere('flags', 'like', '%,'.$flag.',%')
                    ->orWhere('flags', 'like', '%,'.$flag);
            });
        }
        if ($request->filled('q')) {
            $kw = trim((string) $request->query('q'));
            $q->where(function ($w) use ($kw) {
                $w->where('search_text', 'like', "%{$kw}%")
                    ->orWhere('title', 'like', "%{$kw}%");
            });
        }

        $page = $q->paginate($limit);

        return $this->ok([
            'list' => $page->getCollection()->map(fn (Entry $e) => $this->transform($e, false))->values(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'limit' => $page->perPage(),
        ]);
    }

    public function details(int $id): JsonResponse
    {
        $e = Entry::query()->with(['category', 'modelDef', 'tags'])->published()->find($id);
        if (! $e) {
            return $this->fail('内容不存在', 404);
        }

        $memberOnly = filter_var($e->attr('member_only', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($e->attr('require_login', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($e->attr('islogin', false), FILTER_VALIDATE_BOOLEAN)
            || $e->hasFlag('member');
        if ($memberOnly) {
            return $this->fail('该内容需会员权限，请使用前台会话访问', 403);
        }

        return $this->ok($this->transform($e, true));
    }

    /** @return array<string, mixed> */
    protected function transform(Entry $e, bool $detail): array
    {
        $data = [
            'id' => $e->id,
            'title' => $e->t('title'),
            'slug' => $e->t('slug'),
            'excerpt' => $e->t('excerpt'),
            'cover' => $e->cover,
            'flags' => $e->flags,
            'url' => $e->url,
            'views' => $e->views,
            'category_id' => $e->category_id,
            'category' => $e->category?->t('title'),
            'model_id' => $e->model_id,
            'model' => $e->modelDef?->slug,
            'published_at' => optional($e->published_at)?->toIso8601String(),
            'tags' => $e->tags->map(fn ($t) => ['id' => $t->id, 'title' => $t->t('title'), 'slug' => $t->t('slug')])->values(),
        ];
        if ($detail) {
            $data['content'] = $e->t('content');
            $data['attrs'] = $e->attrs ?? [];
            $data['meta_title'] = $e->t('meta_title');
            $data['meta_description'] = $e->t('meta_description');
        }

        return $data;
    }
}
