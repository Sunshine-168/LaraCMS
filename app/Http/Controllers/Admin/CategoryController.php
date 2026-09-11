<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Services\Admin\CategoryService;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    /** 栏目列表 */
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => $this->categoryService->all(),
        ]);
    }

    /** 新建栏目 */
    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new Category(['status' => 'published', 'type' => 'list', 'is_menu' => true, 'page_size' => 10]),
            'locales' => Locale::codes(),
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'parents' => Category::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /** 保存栏目 */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->categoryService->save(null, $request->all());

        return redirect()->route('admin.categories.index')->with('status', $result['msg']);
    }

    /** 编辑栏目 */
    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'locales' => Locale::codes(),
            'models' => ContentModel::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'parents' => Category::query()->where('id', '!=', $category->id)->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /** 更新栏目 */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $result = $this->categoryService->save($category, $request->all());

        return redirect()->route('admin.categories.index')->with('status', $result['msg']);
    }

    /** 删除栏目 */
    public function destroy(Category $category): RedirectResponse
    {
        $result = $this->categoryService->delete($category);

        return redirect()->route('admin.categories.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['msg']
        );
    }
}
