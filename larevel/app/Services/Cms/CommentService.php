<?php

namespace App\Services\Cms;

use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\Setting;
use App\Support\Captcha;
use App\Support\CurrentSite;
use App\Support\MailConfig;
use App\Support\Result;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class CommentService
{
    public function __construct(private readonly HtmlCacheService $htmlCache)
    {
    }

    public function enabled(): bool
    {
        return (bool) (int) Setting::getValue('comments_enabled', '1');
    }

    public function guestsAllowed(): bool
    {
        return (bool) (int) Setting::getValue('comments_guest', '1');
    }

    /** @return \Illuminate\Support\Collection<int, Comment> */
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

    public function submit(Entry $entry, array $input, ?Member $member, string $ip, ?string $ua): array
    {
        if (! $this->enabled()) {
            return Result::fail('评论功能已关闭');
        }
        if ($entry->status !== 'published') {
            return Result::fail('内容不可评论');
        }
        if (! $member && ! $this->guestsAllowed()) {
            return Result::fail('请先登录后再评论');
        }
        if (config('cms.form_captcha', true) && ! Captcha::check($input['captcha'] ?? null)) {
            return Result::fail('验证码错误');
        }

        $siteId = CurrentSite::idOrDefault() ?? (int) $entry->site_id;
        $rateKey = 'cms.comment.rate.'.$siteId.'.'.$ip;
        $seconds = max(10, (int) Setting::getValue('comments_rate_seconds', '60'));
        if (Cache::has($rateKey)) {
            return Result::fail("发表过于频繁，请 {$seconds} 秒后再试");
        }

        $parentId = ! empty($input['parent_id']) ? (int) $input['parent_id'] : null;
        if ($parentId) {
            $parent = Comment::query()->approved()->where('entry_id', $entry->id)->find($parentId);
            if (! $parent) {
                return Result::fail('回复的评论不存在或未通过审核');
            }
        }

        $name = trim((string) ($input['author_name'] ?? ''));
        $email = trim((string) ($input['author_email'] ?? ''));
        if ($member) {
            $name = $name !== '' ? $name : (string) $member->name;
            $email = $email !== '' ? $email : (string) $member->email;
        }
        if ($name === '') {
            return Result::fail('请填写昵称');
        }

        $content = trim((string) ($input['content'] ?? ''));
        if (mb_strlen($content) < 2 || mb_strlen($content) > 2000) {
            return Result::fail('评论内容需在 2～2000 字之间');
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

        $msg = $status === Comment::STATUS_APPROVED
            ? '评论已发布'
            : '评论已提交，通过审核后显示';

        return Result::ok($comment, $msg);
    }

    protected function notifyAdmin(Entry $entry, Comment $comment): void
    {
        $to = trim((string) Setting::getValue('comments_notify_email', ''));
        if ($to === '') {
            return;
        }
        try {
            MailConfig::applyFromSettings();
            $body = "内容：{$entry->t('title')}\n"
                ."作者：{$comment->author_name}".($comment->author_email ? " <{$comment->author_email}>" : '')."\n"
                ."状态：{$comment->status}\n"
                ."IP：{$comment->ip}\n\n"
                ."{$comment->content}\n";
            Mail::raw($body, function ($message) use ($to, $entry) {
                $message->to($to)->subject('【CMS】新评论：'.$entry->t('title'));
            });
        } catch (\Throwable) {
            // 邮件失败不阻断提交
        }
    }
}
