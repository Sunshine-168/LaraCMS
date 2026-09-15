<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsLocale;
use App\Services\Admin\LocaleAdminService;
use App\Support\CurrentSite;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocaleController extends Controller
{
    public function __construct(private readonly LocaleAdminService $locales) {}

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'content');
        if (! in_array($tab, ['content', 'admin', 'theme'], true)) {
            $tab = 'content';
        }

        return view('admin.locales.index', [
            'locales' => $this->locales->all(),
            'tab' => $tab,
            'coverage' => $this->locales->coverage(),
            'themeCatalog' => \App\Support\ThemeUi::catalog(),
            'themeStored' => \App\Support\ThemeUi::stored(),
            'adminUi' => \App\Support\AdminUi::current(),
            'siteAdminUi' => \App\Models\Cms\Setting::getValue('admin_ui_locale', \App\Support\AdminUi::ZH) ?: \App\Support\AdminUi::ZH,
        ]);
    }

    public function switchUi(Request $request): RedirectResponse
    {
        $result = $this->locales->saveAdminUi((string) $request->input('ui_locale'), true);
        $redirect = back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
        if ($result['ok'] && ! empty($result['data']['cookie'])) {
            $redirect->withCookie($result['data']['cookie']);
        }

        return $redirect;
    }

    public function saveUi(Request $request): RedirectResponse
    {
        $mine = $this->locales->saveAdminUi((string) $request->input('ui_locale'), true);
        if (! $mine['ok']) {
            return back()->with('error', $mine['msg']);
        }
        $site = $this->locales->saveAdminUi((string) $request->input('site_ui_locale', $request->input('ui_locale')), false);
        $go = redirect()->route('admin.locales.index', ['tab' => 'admin'])->with('status', $mine['msg']);
        if ($mine['ok'] && ! empty($mine['data']['cookie'])) {
            $go->withCookie($mine['data']['cookie']);
        }
        if (! $site['ok']) {
            return $go->with('error', $site['msg']);
        }

        return $go;
    }

    public function saveTheme(Request $request): RedirectResponse
    {
        $result = $this->locales->saveThemeUi((array) $request->input('theme_ui', []));

        return redirect()->route('admin.locales.index', ['tab' => 'theme'])->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function create(): View
    {
        return view('admin.locales.form', [
            'locale' => new CmsLocale(['is_active' => true, 'hide_default' => true, 'sort' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeCode($request);
        $request->validate($this->rules(), $this->messages());
        $result = $this->locales->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }
        $go = redirect()->route('admin.locales.index', ['tab' => 'content'])->with('status', $result['msg']);
        if (! $request->boolean('from_form')) {
            $go->with('locale_quick_added', true);
        }

        return $go;
    }

    public function edit(CmsLocale $locale): View
    {
        return view('admin.locales.form', compact('locale'));
    }

    public function update(Request $request, CmsLocale $locale): RedirectResponse
    {
        $request->merge(['code' => $locale->code]);
        $request->validate($this->rules($locale), $this->messages());
        $result = $this->locales->save($locale, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.locales.index', ['tab' => 'content'])->with('status', $result['msg']);
    }

    public function destroy(CmsLocale $locale): RedirectResponse
    {
        $result = $this->locales->delete($locale);

        return redirect()->route('admin.locales.index', ['tab' => 'content'])->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function toggle(CmsLocale $locale): RedirectResponse
    {
        $result = $this->locales->toggle($locale);

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function makeDefault(CmsLocale $locale): RedirectResponse
    {
        $result = $this->locales->makeDefault($locale);

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    /** @return array<string, mixed> */
    private function rules(?CmsLocale $locale = null): array
    {
        $unique = Rule::unique('cms_locales', 'code')->where(fn ($q) => $q->where('site_id', CurrentSite::idOrDefault()));
        if ($locale) {
            $unique->ignore($locale->id);
        }

        return [
            'code' => ['required', 'string', 'max:16', 'regex:/^[a-z]{2}(_[a-z]{2})?$/', $unique],
            'label' => 'required|string|max:80',
            'native' => 'nullable|string|max:80',
            'sort' => 'nullable|integer|min:0|max:9999',
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'code.required' => trans('admin.locale.err_code'),
            'code.regex' => trans('admin.locale.err_code_format'),
            'code.unique' => trans('admin.locale.err_code_taken'),
            'label.required' => trans('admin.locale.err_label'),
        ];
    }

    private function normalizeCode(Request $request): void
    {
        $request->merge([
            'code' => Locale::normalize((string) $request->input('code')),
        ]);
    }
}
