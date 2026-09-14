<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Services\Admin\EntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecycleController extends Controller
{
    public function __construct(private readonly EntryService $entries) {}

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $modelId = $request->integer('model_id') ?: null;

        return view('admin.recycle.index', [
            'entries' => $this->entries->paginateTrashed(20, $keyword, $modelId),
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'q' => $keyword,
            'modelId' => $modelId,
        ]);
    }

    public function restore(Request $request, int $entry): RedirectResponse
    {
        $model = Entry::onlyTrashed()->findOrFail($entry);
        $result = $this->entries->restore($model);

        return redirect()->route('admin.recycle.index', $this->listQuery($request))
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function forceDestroy(Request $request, int $entry): RedirectResponse
    {
        $model = Entry::onlyTrashed()->findOrFail($entry);
        $result = $this->entries->forceDelete($model);

        return redirect()->route('admin.recycle.index', $this->listQuery($request))
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function empty(): RedirectResponse
    {
        $result = $this->entries->emptyTrash();

        return redirect()->route('admin.recycle.index')->with('status', $result['msg']);
    }

    public function batch(Request $request): RedirectResponse
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        $action = (string) $request->input('action');
        $result = match ($action) {
            'restore' => $this->entries->restoreMany($ids),
            'delete' => $this->entries->forceDeleteMany($ids),
            default => ['ok' => false, 'msg' => '未知操作'],
        };

        return redirect()->route('admin.recycle.index', $this->listQuery($request))
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    /** @return array<string, int|string> */
    protected function listQuery(Request $request): array
    {
        return array_filter([
            'model_id' => $request->input('model_id', $request->input('keep_model_id')),
            'q' => $request->input('q', $request->input('keep_q')),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
