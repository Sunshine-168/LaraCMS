<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Cms\HtmlCacheService;
use App\Services\Cms\ThemeCatalog;
use App\Services\Cms\ThemePackInstaller;
use App\Services\Cms\ThemeTemplateService;
use App\Support\Locale;
use App\Support\ThemeUi;
use App\Support\ThemeVars;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct(
        private readonly ThemeCatalog $themes,
        private readonly HtmlCacheService $htmlCache,
        private readonly ThemePackInstaller $packs,
        private readonly ThemeTemplateService $templates,
    ) {}

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

    public function show(string $theme): View
    {
        $item = $this->themes->get($theme);
        if (! $item) {
            abort(404);
        }
        $vars = ThemeVars::all($theme);
        $schema = ThemeVars::schema($theme);
        foreach ($schema as &$field) {
            $field['current'] = $vars[$field['key']] ?? $field['value'];
        }
        unset($field);

        return view('admin.themes.show', [
            'theme' => $item,
            'models' => $this->themes->choosableModels($theme),
            'selected' => array_column($item['models'], 'slug'),
            'fields' => $schema,
            'locales' => Locale::configured(),
            'phrases' => ThemeUi::storedFor($theme),
            'phraseKeys' => ThemeUi::packKeys(),
            'phraseCatalog' => ThemeUi::catalog(),
            'categories' => $this->themes->packCategories($theme),
        ]);
    }

    public function update(Request $request, string $theme): RedirectResponse
    {
        $item = $this->themes->get($theme);
        if (! $item) {
            abort(404);
        }

        $this->themes->saveSetup($theme, [
            'i18n' => $request->boolean('i18n'),
            'responsive' => $request->boolean('responsive'),
            'models' => (array) $request->input('models', []),
        ]);
        ThemeVars::save($theme, (array) $request->input('vars', []));
        ThemeUi::saveFor($theme, (array) $request->input('phrases', []));
        $this->htmlCache->forgetAll('theme-config:'.$theme);

        $msg = '已保存「'.$item['name'].'」的配置';
        if ($item['current']) {
            $pack = $this->packs->ensure($theme);
            $extra = trim((string) ($pack['msg'] ?? ''));
            if ($extra !== '') {
                $msg .= '。'.$extra;
            }
        }

        return redirect()
            ->route('admin.themes.show', $theme)
            ->with('status', $msg);
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
            if ($mime === 'image/svg+xml') {
                $raw = (string) file_get_contents($path);

                return response($raw, 200, [
                    'Content-Type' => 'image/svg+xml; charset=utf-8',
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }

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

    public function templates(string $theme): View
    {
        $item = $this->themes->get($theme);
        if (! $item) {
            abort(404);
        }

        return view('admin.themes.templates', [
            'theme' => $item,
            'groups' => $this->templates->groups($theme),
            'writable' => $this->templates->writable($theme),
        ]);
    }

    public function templateRead(Request $request, string $theme): JsonResponse
    {
        if (! $this->themes->get($theme)) {
            abort(404);
        }

        return response()->json($this->templates->read($theme, (string) $request->input('path', '')));
    }

    public function templateSave(Request $request, string $theme): JsonResponse
    {
        if (! $this->themes->get($theme)) {
            abort(404);
        }

        return response()->json($this->templates->save(
            $theme,
            (string) $request->input('path', ''),
            (string) $request->input('content', ''),
        ));
    }

    public function templateBackup(Request $request, string $theme): JsonResponse
    {
        if (! $this->themes->get($theme)) {
            abort(404);
        }

        return response()->json($this->templates->backup($theme, (string) $request->input('path', '')));
    }

    public function templateRollback(Request $request, string $theme): JsonResponse
    {
        if (! $this->themes->get($theme)) {
            abort(404);
        }

        return response()->json($this->templates->rollback($theme, (string) $request->input('path', '')));
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
