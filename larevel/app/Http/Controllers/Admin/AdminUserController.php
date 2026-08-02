<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\Site;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $users)
    {
    }

    public function index(): View
    {
        return view('admin.admin-users.index', [
            'users' => $this->users->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-users.form', [
            'user' => new AdminUser(['is_active' => true]),
            'roles' => $this->users->roles(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'sites' => Site::query()->orderByDesc('is_default')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admin_users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:admin_roles,id',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'integer|exists:cms_sites,id',
        ]);
        $result = $this->users->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.admin-users.index')->with('status', $result['msg']);
    }

    public function edit(AdminUser $admin_user): View
    {
        $admin_user->loadMissing('sites');

        return view('admin.admin-users.form', [
            'user' => $admin_user,
            'roles' => $this->users->roles(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'sites' => Site::query()->orderByDesc('is_default')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, AdminUser $admin_user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admin_users,email,'.$admin_user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:admin_roles,id',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'integer|exists:cms_sites,id',
        ]);
        $result = $this->users->save($admin_user, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.admin-users.index')->with('status', $result['msg']);
    }

    public function destroy(AdminUser $admin_user): RedirectResponse
    {
        $result = $this->users->delete($admin_user, auth('admin')->id());
        if (! $result['ok']) {
            return back()->with('error', $result['msg']);
        }

        return redirect()->route('admin.admin-users.index')->with('status', $result['msg']);
    }
}
