<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Recommend;
use App\Models\Cms\Tag;
use App\Services\Admin\EntryService;
use App\Support\AdminCategoryScope;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntryController extends Controller
{
    public function __construct(private readonly EntryService $entryService)
    {
    }

    /** 内容列表 */
    public function index(Request $request): View
    {
        $modelId = $request->integer('model_id') ?: null;

        return view('admin.entries.index', [
            'entries' => $this->entryService->paginate($modelId),
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'modelId' => $modelId,
            'categories' => $this->scopedCategories(),
            'recommends' => Recommend::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    protected function scopedCategories(?int $modelId = null)
    {
        $q = Category::query()->orderBy('sort_order');
        if ($modelId) {
            $q->where('model_id', $modelId);
        }
        AdminCategoryScope::applyToCategories($q);

        return $q->get();
    }

    /** 新建内容 */
    public function create(Request $request): View
    {
        $model = ContentModel::query()->findOrFail($request->integer('model_id') ?: ContentModel::query()->value('id'));

        return view('admin.entries.form', [
            'entry' => new Entry(['status' => 'draft', 'model_id' => $model->id, 'attrs' => []]),
            'model' => $model->load(['fields.categories']),
            'categories' => $this->scopedCategories($model->id),
            'tags' => Tag::query()->orderByDesc('id')->get(),
            'locales' => Locale::codes(),
            'mapDefaults' => [
                'lng' => (float) \App\Models\Cms\Setting::getValue('map_default_lng', '116.397'),
                'lat' => (float) \App\Models\Cms\Setting::getValue('map_default_lat', '39.908'),
            ],
        ]);
    }

    /** 保存新建 */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->entryService->save(null, $request->all());

        return redirect()->route('admin.entries.index', ['model_id' => $request->input('model_id')])
            ->with('status', $result['msg']);
    }

    /** 编辑内容 */
    public function edit(Entry $entry): View
    {
        $entry->load(['tags', 'categories']);
        $model = $entry->modelDef()->with(['fields.categories'])->firstOrFail();

        return view('admin.entries.form', [
            'entry' => $entry,
            'model' => $model,
            'categories' => $this->scopedCategories($model->id),
            'tags' => Tag::query()->orderByDesc('id')->get(),
            'locales' => Locale::codes(),
            'mapDefaults' => [
                'lng' => (float) \App\Models\Cms\Setting::getValue('map_default_lng', '116.397'),
                'lat' => (float) \App\Models\Cms\Setting::getValue('map_default_lat', '39.908'),
            ],
        ]);
    }

    /** 更新内容 */
    public function update(Request $request, Entry $entry): RedirectResponse
    {
        $result = $this->entryService->save($entry, $request->all());

        return redirect()->route('admin.entries.index', ['model_id' => $entry->model_id])
            ->with('status', $result['msg']);
    }

    /** 删除内容（进回收站） */
    public function destroy(Entry $entry): RedirectResponse
    {
        $modelId = $entry->model_id;
        $result = $this->entryService->delete($entry);

        return redirect()->route('admin.entries.index', ['model_id' => $modelId])
            ->with('status', $result['msg']);
    }

    /** 批量操作 */
    public function batch(Request $request): RedirectResponse
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        $action = (string) $request->input('action');
        $result = match ($action) {
            'publish' => $this->entryService->batchStatus($ids, 'published'),
            'draft' => $this->entryService->batchStatus($ids, 'draft'),
            'delete' => $this->entryService->batchDelete($ids),
            'move' => $this->entryService->batchMove($ids, (int) $request->input('category_id')),
            'copy' => $this->entryService->batchCopy($ids, $request->filled('category_id') ? (int) $request->input('category_id') : null),
            'push' => $this->entryService->batchPush($ids, (int) $request->input('recommend_id')),
            default => ['ok' => false, 'msg' => '未知操作'],
        };

        return redirect()->route('admin.entries.index', ['model_id' => $request->input('model_id')])
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }
}
