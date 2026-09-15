<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminRole;
use App\Services\Admin\RoleAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $request->validate($this->rules(), $this->messages());
        $result = $this->roles->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }
        $go = redirect()->route('admin.roles.index')->with('status', $result['msg']);
        if (! $request->boolean('from_form')) {
            $go->with('role_quick_added', true);
        }

        return $go;
    }

    public function edit(AdminRole $role): View
    {
        $role->load('permissions')->loadCount('users');

        return view('admin.roles.form', [
            'role' => $role,
            'permissionGroups' => $this->roles->permissionTree(),
            'selected' => $role->permissions->pluck('key')->all(),
        ]);
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        $request->validate($this->rules($role), $this->messages());
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

    /** @return array<string, mixed> */
    private function rules(?AdminRole $role = null): array
    {
        $slug = Rule::unique('admin_roles', 'slug');
        if ($role) {
            $slug->ignore($role->id);
        }

        return [
            'name' => 'required|string|max:80',
            'slug' => ['nullable', 'string', 'max:80', $slug],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => '请填写名称',
            'slug.unique' => '这个标识码已经有了',
        ];
    }
}
