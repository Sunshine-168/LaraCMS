<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Site;
use App\Services\Admin\SiteService;
use App\Support\AdminSiteAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function __construct(private readonly SiteService $sites)
    {
    }

    public function index(): View
    {
        return view('admin.sites.index', [
            'sites' => $this->sites->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.sites.form', [
            'site' => new Site(['is_active' => true]),
            'themes' => (array) config('cms.themes', []),
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

        return redirect()->route('admin.sites.index')->with('status', $result['msg']);
    }

    public function edit(Site $site): View
    {
        return view('admin.sites.form', [
            'site' => $site,
            'themes' => (array) config('cms.themes', []),
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

        return back()->with('status', '已切换到：'.$site->name);
    }
}
