<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminRole;
use App\Services\Admin\RoleAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly RoleAdminService $roles)
    {
    }

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => $this->roles->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'role' => new AdminRole(['is_super' => false]),
            'permissionGroups' => $this->roles->permissionTree(),
            'selected' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'nullable|string|max:80|unique:admin_roles,slug',
        ]);
        $result = $this->roles->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.roles.index')->with('status', $result['msg']);
    }

    public function edit(AdminRole $role): View
    {
        $role->load('permissions');

        return view('admin.roles.form', [
            'role' => $role,
            'permissionGroups' => $this->roles->permissionTree(),
            'selected' => $role->permissions->pluck('key')->all(),
        ]);
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'nullable|string|max:80|unique:admin_roles,slug,'.$role->id,
        ]);
        $result = $this->roles->save($role, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.roles.index')->with('status', $result['msg']);
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        $result = $this->roles->delete($role);
        if (! $result['ok']) {
            return back()->with('error', $result['msg']);
        }

        return redirect()->route('admin.roles.index')->with('status', $result['msg']);
    }
}
