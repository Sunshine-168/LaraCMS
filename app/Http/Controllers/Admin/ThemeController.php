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

    public function index(Request $request): View
    {
        $add = session('theme_add', $request->query('add'));
        if (! in_array($add, ['copy', 'upload'], true)) {
            $add = '';
        }

        return view('admin.themes.index', [
            'themes' => $this->themes->all(),
            'current' => $this->themes->currentSlug(),
            'add' => $add,
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

        $redirect = redirect()
            ->route('admin.themes.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
        if (! $result['ok']) {
            $redirect->with('theme_add', 'copy')->withInput();
        }

        return $redirect;
    }

    public function upload(Request $request): RedirectResponse
    {
        $file = $request->file('pack');
        if (! $file || ! $file->isValid()) {
            return redirect()
                ->route('admin.themes.index')
                ->with('error', '请选择 zip 文件')
                ->with('theme_add', 'upload');
        }
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return redirect()
                ->route('admin.themes.index')
                ->with('error', '请上传 zip')
                ->with('theme_add', 'upload');
        }

        $hint = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $result = $this->themes->installZip(
            (string) $file->getRealPath(),
            (string) $request->input('slug', ''),
            (string) $request->input('name', ''),
            is_string($hint) ? $hint : '',
        );

        $redirect = redirect()
            ->route('admin.themes.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
        if (! $result['ok']) {
            $redirect->with('theme_add', 'upload')->withInput();
        }

        return $redirect;
    }

    public function preview(string $theme): \Symfony\Component\HttpFoundation\Response
    {
        $item = $this->themes->get($theme);
        if (! $item) {
            abort(404);
        }
        $path = $this->themes->previewPath($theme);
        $mime = $this->themes->previewMime($path);
        if ($path && $mime) {
            return response()->file($path, [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, max-age=86400',
            ]);
        }

        return response($this->themes->previewSvg($theme), 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'private, max-age=3600',
        ]);
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
