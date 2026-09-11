<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Entry;
use App\Services\Admin\EntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecycleController extends Controller
{
    public function __construct(private readonly EntryService $entries)
    {
    }

    public function index(): View
    {
        return view('admin.recycle.index', [
            'entries' => $this->entries->paginateTrashed(),
        ]);
    }

    public function restore(int $entry): RedirectResponse
    {
        $model = Entry::onlyTrashed()->findOrFail($entry);
        $result = $this->entries->restore($model);

        return redirect()->route('admin.recycle.index')->with('status', $result['msg']);
    }

    public function forceDestroy(int $entry): RedirectResponse
    {
        $model = Entry::onlyTrashed()->findOrFail($entry);
        $result = $this->entries->forceDelete($model);

        return redirect()->route('admin.recycle.index')->with('status', $result['msg']);
    }
}
