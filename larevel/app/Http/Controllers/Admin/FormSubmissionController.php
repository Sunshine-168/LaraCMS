<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsForm;
use App\Models\Cms\FormSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    /** 留言收件箱 */
    public function index(Request $request): View
    {
        $q = FormSubmission::query()->with('form')->orderByDesc('id');
        if ($request->filled('form_id')) {
            $q->where('form_id', (int) $request->input('form_id'));
        }

        return view('admin.forms.submissions', [
            'submissions' => $q->paginate(20)->withQueryString(),
            'forms' => CmsForm::query()->orderBy('name')->get(),
            'formId' => $request->input('form_id'),
        ]);
    }

    /** 导出 CSV */
    public function export(Request $request): StreamedResponse
    {
        $q = FormSubmission::query()->with('form')->orderByDesc('id');
        if ($request->filled('form_id')) {
            $q->where('form_id', (int) $request->input('form_id'));
        }
        $rows = $q->limit(5000)->get();

        $filename = 'form-submissions-'.date('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', '表单', '字段JSON', 'IP', '时间']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->form?->name,
                    json_encode($row->data, JSON_UNESCAPED_UNICODE),
                    $row->ip,
                    (string) $row->created_at,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 删除留言 */
    public function destroy(FormSubmission $submission): RedirectResponse
    {
        $submission->delete();

        return redirect()->route('admin.form-submissions.index')->with('status', '已删除');
    }
}
