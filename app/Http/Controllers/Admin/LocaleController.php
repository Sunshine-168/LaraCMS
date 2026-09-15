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

    public function index(): View
    {
        return view('admin.locales.index', [
            'locales' => $this->locales->all(),
        ]);
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
        $go = redirect()->route('admin.locales.index')->with('status', $result['msg']);
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
        $this->normalizeCode($request);
        $request->validate($this->rules($locale), $this->messages());
        $result = $this->locales->save($locale, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.locales.index')->with('status', $result['msg']);
    }

    public function destroy(CmsLocale $locale): RedirectResponse
    {
        $result = $this->locales->delete($locale);

        return redirect()->route('admin.locales.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
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
            'code.required' => '请填写语言代码，如 en、ja',
            'code.regex' => '代码用 en、ja、zh_cn 这种写法',
            'code.unique' => '这个代码已经有了',
            'label.required' => '请填写名称',
        ];
    }

    private function normalizeCode(Request $request): void
    {
        $request->merge([
            'code' => Locale::normalize((string) $request->input('code')),
        ]);
    }
}
