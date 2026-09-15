<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Category;
use App\Models\Cms\Site;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $users)
    {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $roleKey = (string) $request->query('role', '');
        $roleId = $roleKey !== '' ? (int) $roleKey : null;
        $inactiveOnly = $request->query('off') === '1';

        return view('admin.admin-users.index', [
            'users' => $this->users->paginate(20, $keyword, $roleId, $inactiveOnly),
            'roles' => $this->users->roles(),
            'q' => $keyword,
            'roleKey' => $roleKey,
            'inactiveOnly' => $inactiveOnly,
            'currentId' => auth('admin')->id(),
            'superCount' => $this->users->superAdminCount(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-users.form', [
            'user' => new AdminUser(['is_active' => true]),
            'roles' => $this->users->roles(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'sites' => Site::query()->orderByDesc('is_default')->orderBy('id')->get(),
            'currentId' => auth('admin')->id(),
            'superCount' => $this->users->superAdminCount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(), $this->messages());
        $result = $this->users->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }
        $go = redirect()->route('admin.admin-users.index')->with('status', $result['msg']);
        if (! $request->boolean('from_form')) {
            $go->with('admin_user_quick_added', true);
        }

        return $go;
    }

    public function edit(AdminUser $admin_user): View
    {
        $admin_user->loadMissing('sites');

        return view('admin.admin-users.form', [
            'user' => $admin_user,
            'roles' => $this->users->roles(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'sites' => Site::query()->orderByDesc('is_default')->orderBy('id')->get(),
            'currentId' => auth('admin')->id(),
            'superCount' => $this->users->superAdminCount(),
        ]);
    }

    public function update(Request $request, AdminUser $admin_user): RedirectResponse
    {
        $request->validate($this->rules($admin_user), $this->messages());
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

    public function toggle(AdminUser $admin_user): RedirectResponse
    {
        $result = $this->users->toggle($admin_user, auth('admin')->id());

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    /** @return array<string, mixed> */
    private function rules(?AdminUser $user = null): array
    {
        $email = Rule::unique('admin_users', 'email');
        if ($user) {
            $email->ignore($user->id);
        }

        return [
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', $email],
            'password' => ($user?->exists ? 'nullable' : 'required').'|string|min:8',
            'role_id' => 'required|exists:admin_roles,id',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'integer|exists:cms_sites,id',
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => '请填写名称',
            'email.required' => '请填写登录邮箱',
            'email.email' => '请填写正确的邮箱',
            'email.unique' => '这个邮箱已经有了',
            'password.required' => '请设置登录密码',
            'password.min' => '密码至少 8 位',
            'role_id.required' => '请选择角色',
            'role_id.exists' => '请选择角色',
        ];
    }
}
