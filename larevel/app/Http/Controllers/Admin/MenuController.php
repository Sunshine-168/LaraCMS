<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Menu;
use App\Services\Admin\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService)
    {
    }

    /** 菜单列表 */
    public function index(): View
    {
        return view('admin.menus.index', [
            'menus' => $this->menuService->all(),
        ]);
    }

    /** 新建菜单 */
    public function create(): View
    {
        return view('admin.menus.form', [
            'menu' => new Menu(['is_active' => true, 'location' => 'header']),
        ]);
    }

    /** 保存菜单 */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->menuService->save(null, $request->all());

        return redirect()->route('admin.menus.index')->with('status', $result['msg']);
    }

    /** 编辑菜单 */
    public function edit(Menu $menu): View
    {
        $menu->load('items');

        return view('admin.menus.form', compact('menu'));
    }

    /** 更新菜单 */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $result = $this->menuService->save($menu, $request->all());

        return redirect()->route('admin.menus.index')->with('status', $result['msg']);
    }

    /** 删除菜单 */
    public function destroy(Menu $menu): RedirectResponse
    {
        $result = $this->menuService->delete($menu);

        return redirect()->route('admin.menus.index')->with('status', $result['msg']);
    }
}
