<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\CmsLocale;
use App\Support\Locale;
use App\Support\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocaleController extends Controller
{
    public function index(): View
    {
        return view('admin.locales.index', [
            'locales' => CmsLocale::query()->orderBy('sort')->orderBy('id')->get(),
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
        $data = $request->validate([
            'code' => 'required|string|max:16|unique:cms_locales,code|regex:/^[a-z]{2}(_[a-z]{2})?$/',
            'label' => 'required|string|max:80',
            'native' => 'nullable|string|max:80',
            'sort' => 'nullable|integer|min:0|max:9999',
        ]);
        $result = $this->save(null, $request->all() + $data);

        return redirect()->route('admin.locales.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function edit(CmsLocale $locale): View
    {
        return view('admin.locales.form', compact('locale'));
    }

    public function update(Request $request, CmsLocale $locale): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:16|unique:cms_locales,code,'.$locale->id.'|regex:/^[a-z]{2}(_[a-z]{2})?$/',
            'label' => 'required|string|max:80',
        ]);
        $result = $this->save($locale, $request->all());

        return redirect()->route('admin.locales.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function destroy(CmsLocale $locale): RedirectResponse
    {
        if ($locale->is_default) {
            return back()->with('error', '不能删除默认语言');
        }
        $locale->delete();
        Locale::forgetCache();

        return redirect()->route('admin.locales.index')->with('status', '已删除');
    }

    /** @param  array<string, mixed>  $input */
    protected function save(?CmsLocale $locale, array $input): array
    {
        $locale ??= new CmsLocale;
        $locale->code = Locale::normalize((string) ($input['code'] ?? ''));
        $locale->label = $input['label'] ?? $locale->label;
        $locale->native = $input['native'] ?? $locale->label;
        $locale->sort = (int) ($input['sort'] ?? 0);
        $locale->is_active = ! empty($input['is_active']);
        $locale->hide_default = ! empty($input['hide_default']);
        $makeDefault = ! empty($input['is_default']);
        $locale->is_default = $makeDefault || (! $locale->exists && ! CmsLocale::query()->where('is_default', true)->exists());
        $locale->save();

        if ($locale->is_default) {
            CmsLocale::query()->where('id', '!=', $locale->id)->update(['is_default' => false]);
        }
        Locale::forgetCache();

        return Result::ok($locale, '语言已保存');
    }
}
