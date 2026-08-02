<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\Collector;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\ContentModel;
use App\Jobs\RunCollectorsJob;
use App\Services\Admin\CollectorAdminService;
use App\Services\Cms\Collect\CollectorService;
use App\Support\CmsRuntime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectorController extends Controller
{
    public function __construct(
        private readonly CollectorAdminService $adminService,
        private readonly CollectorService $collectorService,
    ) {
    }

    /** 采集器列表 */
    public function index(): View
    {
        return view('admin.collectors.index', [
            'collectors' => $this->adminService->paginate(),
            'collectMode' => CmsRuntime::collectMode(),
            'collectQueue' => CmsRuntime::queueName('collect'),
            'scheduleEnabled' => CmsRuntime::scheduleEnabled(),
        ]);
    }

    /** 新建 */
    public function create(): View
    {
        return view('admin.collectors.form', [
            'collector' => new Collector([
                'type' => 'rss',
                'interval_minutes' => 60,
                'limit_items' => 10,
                'is_active' => true,
                'publish_immediately' => true,
            ]),
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    /** 保存新建 */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'source_url' => 'required|url',
            'model_id' => 'required|exists:cms_models,id',
            'type' => 'required|in:rss,json',
        ]);
        $result = $this->adminService->save(null, $request->all());

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    /** 编辑 */
    public function edit(Collector $collector): View
    {
        return view('admin.collectors.form', [
            'collector' => $collector,
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'logs' => $collector->logs()->limit(20)->get(),
        ]);
    }

    /** 更新 */
    public function update(Request $request, Collector $collector): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'source_url' => 'required|url',
            'model_id' => 'required|exists:cms_models,id',
            'type' => 'required|in:rss,json',
        ]);
        $result = $this->adminService->save($collector, $request->all());

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    /** 删除 */
    public function destroy(Collector $collector): RedirectResponse
    {
        $result = $this->adminService->delete($collector);

        return redirect()->route('admin.collectors.index')->with('status', $result['msg']);
    }

    /** 立即运行（queue 模式入队，否则同步） */
    public function run(Collector $collector): RedirectResponse
    {
        if (CmsRuntime::collectMode() === CmsRuntime::MODE_QUEUE) {
            RunCollectorsJob::dispatch($collector->id, true);

            return redirect()->route('admin.collectors.index')->with(
                'status',
                '已投递到队列「'.CmsRuntime::queueName('collect').'」：'.$collector->name
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
}
