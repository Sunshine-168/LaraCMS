<?php

namespace App\Services\Cms;

use App\Events\CommentSubmitted;
use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\Setting;
use App\Support\Captcha;
use App\Support\CurrentSite;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CommentService
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly OpsNotifyService $opsNotify,
    ) {}

    public function enabled(): bool
    {
        return (bool) (int) Setting::getValue('comments_enabled', '1');
    }

    public function guestsAllowed(): bool
    {
        return (bool) (int) Setting::getValue('comments_guest', '1');
    }

    /** @return Collection<int, Comment> */
    public function approvedTreeForEntry(Entry $entry)
    {
        $all = Comment::query()
            ->approved()
            ->where('entry_id', $entry->id)
            ->orderBy('id')
            ->get();

        $byParent = $all->groupBy(fn (Comment $c) => (int) ($c->parent_id ?? 0));
        $roots = $byParent->get(0, collect());

        return $roots->map(function (Comment $root) use ($byParent) {
            $root->setRelation('children', $byParent->get($root->id, collect())->values());

            return $root;
        })->values();
    }

    public function submit(Entry $entry, array $input, ?Member $member, string $ip, ?string $ua, bool $skipCaptcha = false): array
    {
        if (! $this->enabled()) {
            return Result::fail(cms_ui('comment_off'));
        }
        if (! $entry->isLive()) {
            return Result::fail(cms_ui('comment_closed'));
        }
        if (! $member && ! $this->guestsAllowed()) {
            return Result::fail(cms_ui('comment_login_first'));
        }
        if (! $skipCaptcha && config('cms.form_captcha', true) && ! Captcha::check($input['captcha'] ?? null)) {
            return Result::fail(cms_ui('captcha_error'));
        }

        $siteId = CurrentSite::idOrDefault() ?? (int) $entry->site_id;
        $rateKey = 'cms.comment.rate.'.$siteId.'.'.$ip;
        $seconds = max(10, (int) Setting::getValue('comments_rate_seconds', '60'));
        if (Cache::has($rateKey)) {
            return Result::fail(cms_ui('comment_rate', ['n' => $seconds]));
        }

        $parentId = ! empty($input['parent_id']) ? (int) $input['parent_id'] : null;
        if ($parentId) {
            $parent = Comment::query()->approved()->where('entry_id', $entry->id)->find($parentId);
            if (! $parent) {
                return Result::fail(cms_ui('comment_parent_gone'));
            }
        }

        $name = trim((string) ($input['author_name'] ?? ''));
        $email = trim((string) ($input['author_email'] ?? ''));
        if ($member) {
            $name = $name !== '' ? $name : (string) $member->name;
            $email = $email !== '' ? $email : (string) $member->email;
        }
        if ($name === '') {
            return Result::fail(cms_ui('comment_need_name'));
        }

        $content = trim((string) ($input['content'] ?? ''));
        if (mb_strlen($content) < 2 || mb_strlen($content) > 2000) {
            return Result::fail(cms_ui('comment_length'));
        }

        $auto = (bool) (int) Setting::getValue('comments_auto_approve', '0');
        $status = ($auto && $member) ? Comment::STATUS_APPROVED : Comment::STATUS_PENDING;

        $comment = Comment::query()->create([
            'entry_id' => $entry->id,
            'parent_id' => $parentId,
            'member_id' => $member?->id,
            'author_name' => mb_substr($name, 0, 80),
            'author_email' => $email !== '' ? mb_substr($email, 0, 120) : null,
            'content' => $content,
            'status' => $status,
            'ip' => $ip,
            'user_agent' => $ua ? mb_substr($ua, 0, 255) : null,
            'moderated_at' => $status === Comment::STATUS_APPROVED ? now() : null,
        ]);

        Cache::put($rateKey, 1, $seconds);

        if ($status === Comment::STATUS_APPROVED) {
            $this->htmlCache->forgetByEntry($entry);
        }

        $this->notifyAdmin($entry, $comment);
        event(new CommentSubmitted($comment, $entry));

        $msg = $status === Comment::STATUS_APPROVED
            ? cms_ui('comment_published')
            : cms_ui('comment_queued');

        return Result::ok($comment, $msg);
    }

    protected function notifyAdmin(Entry $entry, Comment $comment): void
    {
        $this->opsNotify->comment($entry, $comment);
    }
}
