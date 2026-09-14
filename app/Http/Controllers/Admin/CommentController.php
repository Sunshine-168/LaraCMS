<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
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
        $status = (string) $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'spam', 'all'], true)) {
            $status = 'pending';
        }
        $keyword = trim((string) $request->query('q', ''));
        $entryId = $request->filled('entry_id') ? (int) $request->query('entry_id') : null;

        return view('admin.comments.index', [
            'comments' => $this->comments->paginate(
                $status === 'all' ? null : $status,
                $entryId,
                20,
                $keyword
            ),
            'status' => $status,
            'pendingCount' => $this->comments->pendingCount(),
            'entryId' => $entryId,
            'q' => $keyword,
            'filterEntry' => $entryId ? Entry::query()->find($entryId) : null,
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
