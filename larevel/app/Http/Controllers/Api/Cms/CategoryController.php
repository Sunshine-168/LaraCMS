<?php

namespace App\Http\Controllers\Api\Cms;

use App\Http\Controllers\Api\ApiController;
use App\Services\Cms\Api\CategoryApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    public function __construct(private readonly CategoryApiService $service)
    {
    }

    public function listPage(Request $request): JsonResponse
    {
        [$page, $limit] = $this->pagination($request);
        $filters = $request->all();
        $filters['status'] = $filters['status'] ?? 'normal';
        $filters['lang'] = $filters['lang'] ?? $this->resolveLocale($request);

        return $this->success($this->service->listPage($filters, $page, $limit));
    }

    public function detail(Request $request): JsonResponse
    {
        $filters = $request->only(['id', 'lang', 'name']);

        if (empty($filters['id']) && empty($filters['name'])) {
            return $this->error('Parameter can not be empty');
        }

        $filters['lang'] = $filters['lang'] ?? $this->resolveLocale($request);
        $detail = $this->service->detail($filters);

        return $detail
            ? $this->success($detail)
            : $this->error('No results were found');
    }

    public function tree(Request $request): JsonResponse
    {
        $filters = [
            'lang' => $request->input('lang', $this->resolveLocale($request)),
            'status' => 'normal',
        ];

        return $this->success($this->service->tree($filters));
    }
}
