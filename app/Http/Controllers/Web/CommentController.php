<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cms\Entry;
use App\Services\Cms\CommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $comments)
    {
    }

    public function store(Request $request, Entry $entry): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => 'nullable|string|max:80',
            'author_email' => 'nullable|email|max:120',
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer',
            'captcha' => 'nullable|string|max:20',
        ], [], cms_validation_attributes());

        $result = $this->comments->submit(
            $entry,
            $data,
            Auth::guard('member')->user(),
            (string) $request->ip(),
            $request->userAgent()
        );

        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return back()->with('status', $result['msg']);
    }
}
