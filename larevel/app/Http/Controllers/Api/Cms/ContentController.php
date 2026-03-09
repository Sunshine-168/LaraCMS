<?php

namespace App\Http\Controllers\Api\Cms;

use App\Http\Controllers\Api\ApiController;
use App\Services\Cms\Api\ContentApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends ApiController
{
    public function __construct(private readonly ContentApiService $service)
    {
    }

    public function listPage(Request $request): JsonResponse
    {
        [$page, $limit] = $this->pagination($request);
        $filters = $request->all();

        if (empty($filters['model_id']) && empty($filters['catid'])) {
            return $this->error('Parameter can not be empty');
        }

        if (! empty($filters['catid']) && ! is_array($filters['catid']) && ! is_numeric($filters['catid'])) {
            $filters['catid'] = array_filter(explode(',', (string) $filters['catid']));
        }

        $filters['status'] = $filters['status'] ?? 'normal';
        $filters['lang'] = $filters['lang'] ?? $this->resolveLocale($request);

        return $this->success($this->service->listPage($filters, $page, $limit));
    }

    public function details(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);

        if ($id <= 0) {
            return $this->error('Parameter id can not be empty');
        }

        [$page] = $this->pagination($request);
        $detail = $this->service->details($id, $page);

        return $detail
            ? $this->success($detail)
            : $this->error('No results were found');
    }
}
