<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsForm;
use App\Models\Cms\FormSubmission;
use App\Services\Admin\FormSubmissionAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    public function __construct(private readonly FormSubmissionAdminService $submissions) {}

    /** 留言收件箱 */
    public function index(Request $request): View
    {
        $formId = $request->integer('form_id') ?: null;
        $status = (string) $request->query('status', '');
        if (! in_array($status, ['unread', 'read', 'replied'], true)) {
            $status = '';
        }
        $keyword = trim((string) $request->query('q', ''));

        return view('admin.forms.submissions', [
            'submissions' => $this->submissions->paginate($formId, $status, 20, $keyword),
            'forms' => CmsForm::query()->orderBy('name')->get(),
            'formId' => $formId,
            'status' => $status,
            'q' => $keyword,
            'unreadCount' => $this->submissions->unreadCount(),
        ]);
    }

    /** 留言详情 */
    public function show(FormSubmission $submission): View
    {
        $submission->load('form');
        $this->submissions->markRead($submission);

        return view('admin.forms.submission_show', [
            'submission' => $submission->fresh(['form']),
        ]);
    }

    /** 保存备注 / 回复 */
    public function update(Request $request, FormSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => 'nullable|string|max:5000',
            'send_reply' => 'nullable|boolean',
        ]);
        $result = $this->submissions->saveNote(
            $submission,
            trim((string) ($data['admin_note'] ?? '')),
            $request->boolean('send_reply')
        );

        $flash = ($result['ok'] ?? false) ? 'status' : 'error';

        return redirect()->route('admin.form-submissions.show', $submission)->with($flash, $result['msg']);
    }

    /** 标为未读 */
    public function unread(FormSubmission $submission): RedirectResponse
    {
        $this->submissions->markRead($submission, false);

        return redirect()->route('admin.form-submissions.index')->with('status', '已标为未读');
    }

    /** 导出 CSV */
    public function export(Request $request): StreamedResponse
    {
        $formId = $request->integer('form_id') ?: null;
        $status = (string) $request->input('status', '');
        $keyword = trim((string) $request->query('q', ''));
        $rows = $this->submissions->exportRows($formId, $status, 5000, $keyword);

        $filename = 'form-submissions-'.date('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', '表单', '已读', '字段JSON', 'IP', '备注', '回复时间', '提交时间']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->form?->name,
                    $row->is_read ? '是' : '否',
                    json_encode($row->data, JSON_UNESCAPED_UNICODE),
                    $row->ip,
                    $row->admin_note,
                    optional($row->replied_at)?->toDateTimeString(),
                    (string) $row->created_at,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 删除留言 */
    public function destroy(Request $request, FormSubmission $submission): RedirectResponse
    {
        $result = $this->submissions->delete($submission);
        $back = array_filter([
            'form_id' => $request->input('keep_form_id'),
            'status' => $request->input('keep_status'),
            'q' => $request->input('keep_q'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('admin.form-submissions.index', $back)->with('status', $result['msg']);
    }
}
