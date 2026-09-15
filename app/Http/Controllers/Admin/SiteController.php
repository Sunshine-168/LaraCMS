<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Site;
use App\Services\Admin\SiteService;
use App\Services\Cms\ThemeCatalog;
use App\Support\AdminSiteAccess;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function __construct(
        private readonly SiteService $sites,
        private readonly ThemeCatalog $themes,
    ) {
    }

    public function index(): View
    {
        return view('admin.sites.index', [
            'sites' => $this->sites->paginate(),
            'currentId' => CurrentSite::id(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sites.form', [
            'site' => new Site(['is_active' => true]),
            'themes' => $this->themeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'domain' => 'required|string|max:191',
        ]);
        $result = $this->sites->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }
        $go = redirect()->route('admin.sites.index')->with('status', $result['msg']);
        if (! $request->boolean('from_form')) {
            $go->with('site_quick_added', true);
        }

        return $go;
    }

    public function edit(Site $site): View
    {
        return view('admin.sites.form', [
            'site' => $site,
            'themes' => $this->themeOptions(),
            'isCurrent' => (int) CurrentSite::id() === (int) $site->id,
        ]);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'domain' => 'required|string|max:191',
        ]);
        $result = $this->sites->save($site, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.sites.index')->with('status', $result['msg']);
    }

    public function destroy(Site $site): RedirectResponse
    {
        $result = $this->sites->delete($site);
        if (! $result['ok']) {
            return back()->with('error', $result['msg']);
        }

        return redirect()->route('admin.sites.index')->with('status', $result['msg']);
    }

    public function toggle(Site $site): RedirectResponse
    {
        $result = $this->sites->toggle($site);

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function makeDefault(Site $site): RedirectResponse
    {
        $result = $this->sites->makeDefault($site);

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id',
        ]);
        $admin = auth('admin')->user();
        $siteId = (int) $data['site_id'];
        if (! AdminSiteAccess::canAccess($admin, $siteId)) {
            return back()->with('error', '无权切换到该站点');
        }
        $site = Site::query()->where('is_active', true)->find($siteId);
        if (! $site) {
            return back()->with('error', '站点不存在或已停用');
        }
        $request->session()->put('admin_site_id', $site->id);

        return back()->with('status', '正在改：'.$site->name);
    }

    /** @return list<array{slug:string,name:string}> */
    protected function themeOptions(): array
    {
        $out = [];
        foreach ($this->themes->all() as $item) {
            if (! $item['complete']) {
                continue;
            }
            $out[] = ['slug' => $item['slug'], 'name' => $item['name']];
        }

        return $out;
    }
}
