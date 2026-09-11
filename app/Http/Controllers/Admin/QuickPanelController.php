<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\QuickPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuickPanelController extends Controller
{
    public function __construct(private readonly QuickPanelService $panels)
    {
    }

    public function edit(): View
    {
        $admin = auth('admin')->user();

        return view('admin.quick_panels.edit', [
            'options' => $this->panels->editOptions($admin),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = auth('admin')->user();
        $keys = array_values(array_filter((array) $request->input('keys', [])));
        $result = $this->panels->saveForAdmin($admin, $keys);

        return redirect()->route('admin.dashboard')->with('status', $result['msg']);
    }
}
