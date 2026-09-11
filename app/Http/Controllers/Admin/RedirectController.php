<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Redirect;
use App\Services\Admin\RedirectAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function __construct(private readonly RedirectAdminService $redirects)
    {
    }

    public function index(): View
    {
        return view('admin.redirects.index', [
            'redirects' => $this->redirects->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.redirects.form', [
            'redirect' => new Redirect(['status_code' => 301, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'source_path' => 'required|string|max:500',
            'target_url' => 'required|string|max:1000',
            'status_code' => 'required|in:301,302',
        ]);
        $result = $this->redirects->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.redirects.index')->with('status', $result['msg']);
    }

    public function edit(Redirect $redirect): View
    {
        return view('admin.redirects.form', compact('redirect'));
    }

    public function update(Request $request, Redirect $redirect): RedirectResponse
    {
        $request->validate([
            'source_path' => 'required|string|max:500',
            'target_url' => 'required|string|max:1000',
            'status_code' => 'required|in:301,302',
        ]);
        $result = $this->redirects->save($redirect, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.redirects.index')->with('status', $result['msg']);
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $result = $this->redirects->delete($redirect);

        return redirect()->route('admin.redirects.index')->with('status', $result['msg']);
    }

    public function toggle(Redirect $redirect): RedirectResponse
    {
        $result = $this->redirects->toggle($redirect);

        return back()->with('status', $result['msg']);
    }
}
