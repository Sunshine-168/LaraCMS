<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Page;
use App\Services\Admin\PageService;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private readonly PageService $pageService)
    {
    }

    /** 页面列表 */
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => $this->pageService->paginate(),
        ]);
    }

    /** 新建页 */
    public function create(): View
    {
        return view('admin.pages.form', [
            'page' => new Page(['status' => 'draft', 'locales' => []]),
            'locales' => Locale::codes(),
        ]);
    }

    /** 保存新建 */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->pageService->save(null, $request->all());

        return redirect()->route('admin.pages.index')->with('status', $result['msg']);
    }

    /** 编辑页 */
    public function edit(Page $page): View
    {
        return view('admin.pages.form', [
            'page' => $page,
            'locales' => Locale::codes(),
        ]);
    }

    /** 更新页面 */
    public function update(Request $request, Page $page): RedirectResponse
    {
        $result = $this->pageService->save($page, $request->all());

        return redirect()->route('admin.pages.index')->with('status', $result['msg']);
    }

    /** 删除页面 */
    public function destroy(Page $page): RedirectResponse
    {
        $result = $this->pageService->delete($page);

        return redirect()->route('admin.pages.index')->with('status', $result['msg']);
    }
}
