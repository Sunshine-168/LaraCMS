<?php

namespace App\Http\Controllers\Api\Cms;

use App\Http\Controllers\Api\ApiController;
use App\Services\Cms\Api\SiteConfigApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteConfigController extends ApiController
{
    public function __construct(private readonly SiteConfigApiService $service)
    {
    }

    public function getAll(Request $request): JsonResponse
    {
        $lang = $request->input('lang', $this->resolveLocale($request));

        return $this->success('获取成功', $this->service->getAll($lang));
    }
}
