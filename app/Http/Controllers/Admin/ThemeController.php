<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Cms\ThemeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct(private readonly ThemeCatalog $themes) {}

    public function index(): View
    {
        return view('admin.themes.index', [
            'themes' => $this->themes->all(),
            'current' => $this->themes->currentSlug(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->themes->copy(
            (string) $request->input('from', 'enterprise'),
            (string) $request->input('slug', ''),
            (string) $request->input('name', ''),
            (string) $request->input('description', ''),
        );

        return redirect()
            ->route('admin.themes.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg'])
            ->with('theme_quick_added', $result['ok']);
    }

    public function apply(string $theme): RedirectResponse
    {
        $result = $this->themes->apply($theme);

        return redirect()
            ->route('admin.themes.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function destroy(string $theme): RedirectResponse
    {
        $result = $this->themes->remove($theme);

        return redirect()
            ->route('admin.themes.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }
}
