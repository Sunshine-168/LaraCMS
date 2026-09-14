<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsForm;
use App\Services\Admin\FormAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormController extends Controller
{
    public function __construct(private readonly FormAdminService $forms)
    {
    }

    /** 表单定义列表 */
    public function index(): View
    {
        return view('admin.forms.defs_index', [
            'forms' => $this->forms->paginate(),
        ]);
    }

    /** 新建 */
    public function create(): View
    {
        return view('admin.forms.defs_form', [
            'form' => new CmsForm([
                'is_active' => true,
                'fields' => [
                    ['name' => 'name', 'label' => '姓名', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => '邮箱', 'type' => 'email', 'required' => true],
                    ['name' => 'message', 'label' => '留言', 'type' => 'textarea', 'required' => true],
                ],
            ]),
        ]);
    }

    /** 保存新建 */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:64',
        ]);
        $result = $this->forms->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.forms.index')->with('status', $result['msg']);
    }

    /** 编辑 */
    public function edit(CmsForm $form): View
    {
        return view('admin.forms.defs_form', compact('form'));
    }

    /** 更新 */
    public function update(Request $request, CmsForm $form): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:64',
        ]);
        $result = $this->forms->save($form, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.forms.index')->with('status', $result['msg']);
    }

    public function toggle(CmsForm $form): RedirectResponse
    {
        $result = $this->forms->toggle($form);

        return redirect()->route('admin.forms.index')->with('status', $result['msg']);
    }

    /** 删除 */
    public function destroy(CmsForm $form): RedirectResponse
    {
        $result = $this->forms->delete($form);

        return redirect()->route('admin.forms.index')->with('status', $result['msg']);
    }
}
