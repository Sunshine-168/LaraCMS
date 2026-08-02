<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function success(mixed $message = 'success', mixed $data = null): JsonResponse
    {
        if (is_array($message)) {
            $data = $message;
            $message = 'success';
        }

        return response()->json([
            'msg' => $message,
            'data' => $data,
            'code' => 200,
        ]);
    }

    protected function error(string $message = 'error', mixed $data = null): JsonResponse
    {
        return response()->json([
            'msg' => $message,
            'data' => $data,
            'code' => 400,
        ]);
    }

    protected function resolveLocale(Request $request): string
    {
        return (string) ($request->input('lang') ?: config('cms.default_locale', 'zh-cn'));
    }

    protected function pagination(Request $request): array
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = max(1, min(100, (int) $request->input('limit', 10)));

        return [$page, $limit];
    }
}
