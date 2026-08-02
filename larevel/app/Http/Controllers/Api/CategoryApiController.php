<?php

namespace App\Http\Controllers\Api;
use App\Models\Cms\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryApiController extends ApiController
{
    public function listPage(Request $request): JsonResponse
    {
        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $q = Category::query()->active()->with('modelDef')->orderBy('sort_order')->orderBy('id');
        if ($request->filled('model_id')) {
            $q->where('model_id', (int) $request->query('model_id'));
        }
        if ($request->filled('parent_id')) {
            $q->where('parent_id', (int) $request->query('parent_id'));
        }
        $page = $q->paginate($limit);

        return $this->ok([
            'list' => $page->getCollection()->map(fn (Category $c) => $this->transform($c))->values(),
            'total' => $page->total(),
            'page' => $page->currentPage(),
            'limit' => $page->perPage(),
        ]);
    }

    public function tree(): JsonResponse
    {
        $all = Category::query()->active()->with('modelDef')->orderBy('sort_order')->get();
        $map = [];
        foreach ($all as $c) {
            $map[$c->id] = $this->transform($c);
            $map[$c->id]['children'] = [];
        }
        $roots = [];
        foreach ($all as $c) {
            $node = &$map[$c->id];
            $pid = (int) ($c->parent_id ?? 0);
            if ($pid > 0 && isset($map[$pid])) {
                $map[$pid]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
            unset($node);
        }

        return $this->ok(array_values($roots));
    }

    public function detail(int $id): JsonResponse
    {
        $c = Category::query()->active()->with('modelDef')->find($id);
        if (! $c) {
            return $this->fail('栏目不存在', 404);
        }

        return $this->ok($this->transform($c));
    }

    /** @return array<string, mixed> */
    protected function transform(Category $c): array
    {
        return [
            'id' => $c->id,
            'title' => $c->t('title'),
            'slug' => $c->t('slug'),
            'type' => $c->type,
            'model_id' => $c->model_id,
            'model' => $c->modelDef?->slug,
            'parent_id' => $c->parent_id,
            'url' => $c->frontUrl(),
            'is_menu' => (bool) $c->is_menu,
        ];
    }
}
