<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunCollectorsJob;
use App\Models\Cms\Category;
use App\Models\Cms\Collector;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\ContentModel;
use App\Services\Admin\CollectorAdminService;
use App\Services\Cms\Collect\CollectorService;
use App\Support\CmsRuntime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectorController extends Controller
{
    public function __construct(
        private readonly CollectorAdminService $adminService,
        private readonly CollectorService $collectorService,
    ) {}

    /** 采集任务列表 */
    public function index(): View
    {
        return view('admin.collectors.index', [
            'collectors' => $this->adminService->paginate(),
            'collectMode' => CmsRuntime::collectMode(),
        ]);
    }

    /** 新建 */
    public function create(): View
    {
        return view('admin.collectors.form', $this->formData(new Collector([
            'type' => 'html',
            'interval_minutes' => 60,
            'limit_items' => 10,
            'is_active' => true,
            'publish_immediately' => false,
        ])));
    }

    /** @return array<string, mixed> */
    protected function formData(Collector $collector, $logs = null): array
    {
        return [
            'collector' => $collector,
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'logs' => $logs,
        ];
    }

    /** 保存新建 */
    public function store(Request $request): RedirectResponse
    {
        $this->validateCollector($request);
        $result = $this->adminService->save(null, $request->all());

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    /** 编辑 */
    public function edit(Collector $collector): View
    {
        return view('admin.collectors.form', $this->formData(
            $collector,
            $collector->logs()->limit(8)->get()
        ));
    }

    /** 更新 */
    public function update(Request $request, Collector $collector): RedirectResponse
    {
        $this->validateCollector($request);
        $result = $this->adminService->save($collector, $request->all());

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    /** 删除 */
    public function destroy(Collector $collector): RedirectResponse
    {
        $result = $this->adminService->delete($collector);

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    public function toggle(Collector $collector): RedirectResponse
    {
        $result = $this->adminService->toggle($collector);

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    /** 试抓：不写内容 */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'source_url' => 'required|url',
            'type' => 'required|in:rss,json,html',
            'item_selector' => 'required_if:type,html|nullable|string|max:200',
            'limit_items' => 'nullable|integer|min:1|max:100',
            'page_count' => 'nullable|integer|min:1|max:10',
        ]);
        $collector = new Collector($this->adminService->attributesFromInput($request->all()));
        $result = $this->collectorService->preview($collector);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** 立即运行（queue 模式入队，否则同步） */
    public function run(Collector $collector): RedirectResponse
    {
        if (CmsRuntime::collectMode() === CmsRuntime::MODE_QUEUE) {
            RunCollectorsJob::dispatch($collector->id, true);

            return redirect()->route('admin.collectors.index')->with(
                'status',
                '已交给后台队列，稍候可在运行日志里看结果：'.$collector->name
            );
        }

        $result = $this->collectorService->run($collector);

        return redirect()->route('admin.collectors.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['msg']
        );
    }

    /** 日志列表 */
    public function logs(): View
    {
        return view('admin.collectors.logs', [
            'logs' => CollectorLog::query()->with('collector')->orderByDesc('id')->paginate(30),
        ]);
    }

    protected function validateCollector(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'source_url' => 'required|url',
            'model_id' => 'required|exists:cms_models,id',
            'type' => 'required|in:rss,json,html',
            'item_selector' => 'required_if:type,html|nullable|string|max:200',
            'page_count' => 'nullable|integer|min:1|max:10',
            'page_url' => 'nullable|string|max:500',
            'next_selector' => 'nullable|string|max:200',
            'interval_minutes' => 'nullable|integer|min:1',
            'limit_items' => 'nullable|integer|min:1|max:100',
        ]);
    }
}
