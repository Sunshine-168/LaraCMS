<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Comment;
use App\Services\Admin\CommentAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function __construct(private readonly CommentAdminService $comments)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->input('status', 'pending');

        return view('admin.comments.index', [
            'comments' => $this->comments->paginate(
                $status === 'all' ? null : (string) $status,
                $request->filled('entry_id') ? (int) $request->input('entry_id') : null
            ),
            'status' => $status,
            'pendingCount' => $this->comments->pendingCount(),
            'entryId' => $request->input('entry_id'),
        ]);
    }

    public function moderate(Request $request, Comment $comment): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected,spam,pending',
        ]);
        $result = $this->comments->moderate($comment, $data['status'], auth('admin')->id());
        if (! $result['ok']) {
            return back()->with('error', $result['msg']);
        }

        return back()->with('status', $result['msg']);
    }

    public function batch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'status' => 'required|in:approved,rejected,spam,pending',
        ]);
        $result = $this->comments->batchModerate($data['ids'], $data['status'], auth('admin')->id());
        if (! $result['ok']) {
            return back()->with('error', $result['msg']);
        }

        return back()->with('status', $result['msg']);
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $result = $this->comments->delete($comment);

        return back()->with('status', $result['msg']);
    }
}
