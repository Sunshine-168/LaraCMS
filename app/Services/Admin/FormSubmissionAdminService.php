<?php

namespace App\Services\Admin;

use App\Models\Cms\FormSubmission;
use App\Support\MailConfig;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class FormSubmissionAdminService
{
    public function paginate(?int $formId, ?string $status, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($formId, $status)->paginate($perPage)->withQueryString();
    }

    /** @return Collection<int, FormSubmission> */
    public function exportRows(?int $formId, ?string $status, int $limit = 5000)
    {
        return $this->filtered($formId, $status)->limit($limit)->get();
    }

    protected function filtered(?int $formId, ?string $status)
    {
        $q = FormSubmission::query()->with('form')->orderByDesc('id');
        if ($formId) {
            $q->where('form_id', $formId);
        }
        if ($status === 'unread') {
            $q->where('is_read', false);
        } elseif ($status === 'read') {
            $q->where('is_read', true);
        } elseif ($status === 'replied') {
            $q->whereNotNull('replied_at');
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

        try {
            MailConfig::applyFromSettings();
            $formName = $submission->form?->name ?: '留言';
            Mail::raw($note, function ($message) use ($email, $formName) {
                $message->to($email)->subject('回复：'.$formName);
            });
            $submission->replied_at = now();
            $submission->save();
        } catch (\Throwable $e) {
            return Result::fail('邮件发送失败：'.$e->getMessage().'。备注已保存。');
        }

        return Result::ok($submission, '已保存并发送回复');
    }

    public function delete(FormSubmission $submission): array
    {
        $submission->delete();

        return Result::ok(null, '已删除');
    }
}
