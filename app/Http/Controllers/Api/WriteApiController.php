<?php

namespace App\Http\Controllers\Api;

use App\Http\Middleware\ApiToken;
use App\Models\Cms\Entry;
use App\Services\Cms\CommentService;
use App\Services\Cms\FormSubmitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WriteApiController extends ApiController
{
    public function __construct(
        private readonly FormSubmitService $forms,
        private readonly CommentService $comments,
    ) {}

    public function form(Request $request, string $slug): JsonResponse
    {
        $result = $this->forms->submit(
            $slug,
            $request,
            Auth::guard('member')->user(),
            ApiToken::providedTokenIsValid($request)
        );
        if (! $result['ok']) {
            $code = $result['msg'] === '表单不存在' ? 404 : 400;

            return $this->fail($result['msg'], $code);
        }

        return $this->ok(['id' => $result['data']->id], $result['msg']);
    }

    public function comment(Request $request, int $entry): JsonResponse
    {
        $model = Entry::query()->published()->find($entry);
        if (! $model) {
            return $this->fail('内容不存在', 404);
        }

        $data = $request->validate([
            'author_name' => 'nullable|string|max:80',
            'author_email' => 'nullable|email|max:120',
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer',
            'captcha' => 'nullable|string|max:20',
        ]);

        $result = $this->comments->submit(
            $model,
            $data,
            Auth::guard('member')->user(),
            (string) $request->ip(),
            $request->userAgent(),
            ApiToken::providedTokenIsValid($request)
        );
        if (! $result['ok']) {
            return $this->fail($result['msg']);
        }

        return $this->ok(['id' => $result['data']->id, 'status' => $result['data']->status], $result['msg']);
    }
}
