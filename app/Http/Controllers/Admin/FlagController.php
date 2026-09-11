<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsFlag;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FlagController extends Controller
{
    public function index(): View
    {
        return view('admin.flags.index', [
            'flags' => CmsFlag::query()->orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.flags.form', [
            'flag' => new CmsFlag(['is_active' => true, 'sort' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z][A-Za-z0-9_\-]*$/', Rule::unique('cms_flags', 'name')->where(fn ($q) => $q->where('site_id', CurrentSite::idOrDefault()))],
            'title' => 'required|string|max:80',
            'sort' => 'nullable|integer|min:0|max:9999',
        ]);
        CmsFlag::query()->create([
            'name' => $data['name'],
            'title' => $data['title'],
            'sort' => (int) ($request->input('sort', 0)),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.flags.index')->with('status', '已保存');
    }

    public function edit(CmsFlag $flag): View
    {
        return view('admin.flags.form', compact('flag'));
    }

    public function update(Request $request, CmsFlag $flag): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z][A-Za-z0-9_\-]*$/', Rule::unique('cms_flags', 'name')->where(fn ($q) => $q->where('site_id', CurrentSite::idOrDefault()))->ignore($flag->id)],
            'title' => 'required|string|max:80',
            'sort' => 'nullable|integer|min:0|max:9999',
        ]);
        $flag->fill([
            'name' => $data['name'],
            'title' => $data['title'],
            'sort' => (int) ($request->input('sort', 0)),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return redirect()->route('admin.flags.index')->with('status', '已保存');
    }

    public function destroy(CmsFlag $flag): RedirectResponse
    {
        $flag->delete();

        return redirect()->route('admin.flags.index')->with('status', '已删除');
    }
}
