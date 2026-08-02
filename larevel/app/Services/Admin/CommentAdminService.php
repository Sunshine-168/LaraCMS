<?php

namespace App\Services\Admin;

use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Services\Cms\HtmlCacheService;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentAdminService
{
    public function __construct(private readonly HtmlCacheService $htmlCache)
    {
    }

    public function paginate(?string $status = null, ?int $entryId = null, int $perPage = 20): LengthAwarePaginator
    {
        $q = Comment::query()->with(['entry', 'member', 'moderator'])->orderByDesc('id');
        if ($status && in_array($status, [
            Comment::STATUS_PENDING,
            Comment::STATUS_APPROVED,
            Comment::STATUS_REJECTED,
            Comment::STATUS_SPAM,
        ], true)) {
            $q->where('status', $status);
        }
        if ($entryId) {
            $q->where('entry_id', $entryId);
        }

        return $q->paginate($perPage)->withQueryString();
    }

    public function pendingCount(): int
    {
        return Comment::query()->pending()->count();
    }

    public function moderate(Comment $comment, string $status, ?int $adminId): array
    {
        if (! in_array($status, [
            Comment::STATUS_APPROVED,
            Comment::STATUS_REJECTED,
            Comment::STATUS_SPAM,
            Comment::STATUS_PENDING,
        ], true)) {
            return Result::fail('无效状态');
        }
        $comment->status = $status;
        $comment->moderated_by = $adminId;
        $comment->moderated_at = now();
        $comment->save();

        $comment->loadMissing('entry');
        if ($comment->entry) {
            $this->htmlCache->forgetByEntry($comment->entry);
        }

        return Result::ok($comment, '已更新为：'.Comment::statusLabel($status));
    }

    /** @param list<int> $ids */
    public function batchModerate(array $ids, string $status, ?int $adminId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return Result::fail('请选择评论');
        }
        $n = 0;
        Comment::query()->whereIn('id', $ids)->each(function (Comment $c) use ($status, $adminId, &$n) {
            $r = $this->moderate($c, $status, $adminId);
            if ($r['ok']) {
                $n++;
            }
        });

        return Result::ok(['count' => $n], "已处理 {$n} 条");
    }

    public function delete(Comment $comment): array
    {
        $entry = $comment->entry;
        $comment->delete();
        if ($entry instanceof Entry) {
            $this->htmlCache->forgetByEntry($entry);
        }

        return Result::ok(null, '已删除');
    }
}
