<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    protected function ok(mixed $data = null, string $msg = 'ok'): JsonResponse
    {
        return response()->json(['code' => 200, 'msg' => $msg, 'data' => $data]);
    }

    protected function fail(string $msg, int $code = 400, mixed $data = null): JsonResponse
    {
        return response()->json(['code' => $code, 'msg' => $msg, 'data' => $data], $code >= 100 && $code < 600 ? $code : 400);
    }
}
