<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsFlag;
use App\Services\Admin\FlagAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlagController extends Controller
{
    public function __construct(private readonly FlagAdminService $flags) {}

    public function index(): View
    {
        return view('admin.flags.index', [
            'flags' => $this->flags->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.flags.form', [
            'flag' => new CmsFlag(['is_active' => true, 'sort' => 0]),
            'usage' => 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->flags->save(null, $request->all());
        $redirect = redirect()->route('admin.flags.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
        if ($result['ok'] && ! $request->boolean('from_form')) {
            $redirect->with('flag_quick_added', true);
        }

        return $redirect;
    }

    public function edit(CmsFlag $flag): View
    {
        return view('admin.flags.form', [
            'flag' => $flag,
            'usage' => $this->flags->usageCount($flag),
        ]);
    }

    public function update(Request $request, CmsFlag $flag): RedirectResponse
    {
        $result = $this->flags->save($flag, $request->all());

        return redirect()->route('admin.flags.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function toggle(CmsFlag $flag): RedirectResponse
    {
        $result = $this->flags->toggle($flag);

        return redirect()->route('admin.flags.index')->with('status', $result['msg']);
    }

    public function move(Request $request, CmsFlag $flag): RedirectResponse
    {
        $result = $this->flags->move($flag, (string) $request->input('dir'));

        return redirect()->route('admin.flags.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function destroy(CmsFlag $flag): RedirectResponse
    {
        $result = $this->flags->delete($flag);

        return redirect()->route('admin.flags.index')->with('status', $result['msg']);
    }
}
