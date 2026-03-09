<?php

namespace App\Http\Controllers\Api\Cms;

use App\Http\Controllers\Api\ApiController;
use App\Services\Cms\Api\RecommendApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendController extends ApiController
{
    public function __construct(private readonly RecommendApiService $service)
    {
    }

    public function contentList(Request $request): JsonResponse
    {
        $filters = $request->only(['name', 'itemid', 'num']);

        if (empty($filters['name'])) {
            return $this->error('Parameter name can not be empty');
        }

        $filters['lang'] = $this->resolveLocale($request);
        $data = $this->service->contentList($filters);

        return $data
            ? $this->success($data)
            : $this->error('No results were found');
    }
}
