<?php

namespace App\Services\Admin;

use App\Models\Cms\FormSubmission;
use App\Services\Cms\OpsNotifyService;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FormSubmissionAdminService
{
    public function __construct(private readonly OpsNotifyService $opsNotify) {}
    public function paginate(?int $formId, ?string $status, int $perPage = 20, ?string $keyword = null): LengthAwarePaginator
    {
        return $this->filtered($formId, $status, $keyword)->paginate($perPage)->withQueryString();
    }

    /** @return Collection<int, FormSubmission> */
    public function exportRows(?int $formId, ?string $status, int $limit = 5000, ?string $keyword = null)
    {
        return $this->filtered($formId, $status, $keyword)->limit($limit)->get();
    }

    protected function filtered(?int $formId, ?string $status, ?string $keyword = null)
    {
        $q = FormSubmission::query()->with('form')->orderByDesc('id');
        if ($formId) {
            $q->where('form_id', $formId);
        }
        if ($status === 'unread') {
            $q->where('is_read', false);
        } elseif ($status === 'read') {
            $q->where('is_read', true)->whereNull('replied_at');
        } elseif ($status === 'replied') {
            $q->whereNotNull('replied_at');
        }
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $q->where(function ($inner) use ($like) {
                $inner->where('ip', 'like', $like)
                    ->orWhere('admin_note', 'like', $like);
                foreach (['name', 'message', 'email', 'title', 'content'] as $key) {
                    $inner->orWhere('data->'.$key, 'like', $like);
                }
            });
        }

        return $q;
    }

    public function unreadCount(): int
    {
        return FormSubmission::query()->where('is_read', false)->count();
    }

    public function markRead(FormSubmission $submission, bool $read = true): void
    {
        $submission->is_read = $read;
        $submission->save();
    }

    public function saveNote(FormSubmission $submission, string $note, bool $sendReply = false): array
    {
        $submission->admin_note = $note !== '' ? $note : null;
        $submission->is_read = true;
        $submission->save();

        if (! $sendReply) {
            return Result::ok($submission, '备注已保存');
        }

        $email = $submission->guestEmail();
        if (! $email) {
            return Result::fail('这条留言没有可用邮箱，无法发信。备注已保存。');
        }
        if ($note === '') {
            return Result::fail('请填写回复内容再发送。');
        }

        $formName = $submission->form?->name ?: '留言';
        if (! $this->opsNotify->formReply($email, $formName, $note)) {
            return Result::fail('邮件发送失败。请先在系统设置里配好 SMTP。备注已保存。');
        }
        $submission->replied_at = now();
        $submission->save();

        return Result::ok($submission, '已保存并发送回复');
    }

    public function delete(FormSubmission $submission): array
    {
        $submission->delete();

        return Result::ok(null, '已删除');
    }
}
