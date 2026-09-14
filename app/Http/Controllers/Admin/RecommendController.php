<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Recommend;
use App\Services\Admin\RecommendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendController extends Controller
{
    public function __construct(private readonly RecommendService $recommendService) {}

    /** 推荐位列表 */
    public function index(): View
    {
        return view('admin.recommends.index', [
            'recommends' => $this->recommendService->all(),
        ]);
    }

    /** 新建推荐位 */
    public function create(): View
    {
        return view('admin.recommends.form', $this->formData(new Recommend(['is_active' => true, 'type' => 'image'])));
    }

    /** @return array<string, mixed> */
    protected function formData(Recommend $recommend): array
    {
        return [
            'recommend' => $recommend,
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ];
    }

    /** 保存推荐位 */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->recommendService->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.recommends.index')->with('status', $result['msg']);
    }

    /** 编辑推荐位 */
    public function edit(Recommend $recommend): View
    {
        $recommend->load('items');

        return view('admin.recommends.form', $this->formData($recommend));
    }

    /** 更新推荐位 */
    public function update(Request $request, Recommend $recommend): RedirectResponse
    {
        $result = $this->recommendService->save($recommend, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.recommends.index')->with('status', $result['msg']);
    }

    /** 删除推荐位 */
    public function destroy(Recommend $recommend): RedirectResponse
    {
        $result = $this->recommendService->delete($recommend);

        return redirect()->route('admin.recommends.index')->with('status', $result['msg']);
    }

    public function toggle(Recommend $recommend): RedirectResponse
    {
        $result = $this->recommendService->toggle($recommend);

        return redirect()->route('admin.recommends.index')->with('status', $result['msg']);
    }
}
