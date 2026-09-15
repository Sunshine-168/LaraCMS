<?php

namespace App\Services\Cms;

use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Setting;
use App\Support\MailConfig;

/** 待审稿、评论、表单的运营通知 */
class OpsNotifyService
{
    public function notifyEmail(): string
    {
        return trim((string) Setting::getValue('comments_notify_email', ''));
    }

    public function entryPending(Entry $entry): void
    {
        $to = $this->notifyEmail();
        if ($to === '') {
            return;
        }
        $title = $entry->t('title') ?: '未命名';
        MailConfig::send($to, '待审稿：'.$title, 'mail.notice', [
            'siteName' => (string) Setting::getValue('site_name', config('app.name')),
            'heading' => '有一篇内容待审核',
            'intro' => '后台有一篇待审稿，发布后才会出现在前台。',
            'lines' => [
                '标题' => $title,
                '作者' => $entry->member?->name ?: '管理员',
            ],
            'actionUrl' => route('admin.entries.edit', $entry),
            'actionText' => '去审核',
        ]);
    }

    public function comment(Entry $entry, Comment $comment): void
    {
        $to = $this->notifyEmail();
        if ($to === '') {
            return;
        }
        $title = $entry->t('title') ?: '内容';
        MailConfig::send($to, '新评论：'.$title, 'mail.notice', [
            'siteName' => (string) Setting::getValue('site_name', config('app.name')),
            'heading' => '有一条新评论',
            'intro' => $comment->status === Comment::STATUS_APPROVED ? '已直接发布。' : '默认待审核，通过后才会显示在文章下。',
            'lines' => [
                '文章' => $title,
                '作者' => $comment->author_name.($comment->author_email ? ' <'.$comment->author_email.'>' : ''),
                '状态' => Comment::statusLabel((string) $comment->status),
                'IP' => (string) $comment->ip,
            ],
            'body' => (string) $comment->content,
            'actionUrl' => route('admin.comments.index', ['status' => $comment->status]),
            'actionText' => '去处理',
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function formSubmitted(string $formName, string $to, array $data, string $ip): void
    {
        if (trim($to) === '') {
            return;
        }
        $lines = [];
        foreach ($data as $key => $value) {
            $lines[(string) $key] = is_array($value) ? implode('、', $value) : (string) $value;
        }
        $lines['IP'] = $ip;
        MailConfig::send($to, '新表单留言：'.$formName, 'mail.notice', [
            'siteName' => (string) Setting::getValue('site_name', config('app.name')),
            'heading' => '表单「'.$formName.'」有新提交',
            'intro' => '访客提交了一份表单。',
            'lines' => $lines,
            'actionUrl' => route('admin.form-submissions.index'),
            'actionText' => '查看留言',
        ]);
    }

    public function formReply(string $to, string $formName, string $note): bool
    {
        return MailConfig::send($to, '回复：'.$formName, 'mail.notice', [
            'siteName' => (string) Setting::getValue('site_name', config('app.name')),
            'heading' => '关于你在「'.$formName.'」的留言',
            'intro' => '站点已回复：',
            'body' => $note,
        ]);
    }
}
