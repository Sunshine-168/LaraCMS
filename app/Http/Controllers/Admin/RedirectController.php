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

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        return view('admin.redirects.index', [
            'redirects' => $this->redirects->paginate(20, $keyword),
            'q' => $keyword,
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
        $this->validateRule($request);
        $result = $this->redirects->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        $back = array_filter(['q' => $request->input('keep_q')], fn ($v) => $v !== null && $v !== '');
        $go = redirect()->route('admin.redirects.index', $back)->with('status', $result['msg']);
        if (! $request->boolean('from_form')) {
            $go->with('redirect_quick_added', true);
        }

        return $go;
    }

    public function edit(Redirect $redirect): View
    {
        return view('admin.redirects.form', compact('redirect'));
    }

    public function update(Request $request, Redirect $redirect): RedirectResponse
    {
        $this->validateRule($request);
        $result = $this->redirects->save($redirect, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.redirects.index')->with('status', $result['msg']);
    }

    public function destroy(Request $request, Redirect $redirect): RedirectResponse
    {
        $result = $this->redirects->delete($redirect);
        $back = array_filter(['q' => $request->input('keep_q')], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('admin.redirects.index', $back)->with('status', $result['msg']);
    }

    public function toggle(Redirect $redirect): RedirectResponse
    {
        $result = $this->redirects->toggle($redirect);

        return back()->with('status', $result['msg']);
    }

    protected function validateRule(Request $request): void
    {
        $request->validate([
            'source_path' => 'required|string|max:500',
            'target_url' => 'required|string|max:1000',
            'status_code' => 'nullable|in:301,302',
            'note' => 'nullable|string|max:255',
        ]);
    }
}
