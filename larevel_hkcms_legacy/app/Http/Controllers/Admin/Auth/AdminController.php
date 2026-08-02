<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Admin\BaseAdminController;
use App\Models\Admin\AdminUser;
use App\Models\Admin\AuthGroup;
use App\Models\Admin\AuthGroupAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends BaseAdminController
{
    public function index(Request $request): View
    {
        $query = AdminUser::query()->orderBy('id');
        $allowedIds = $this->auth->manageableAdminIds();
        $query->whereIn('id', $allowedIds ?: [0]);

        if ($value = trim((string) $request->query('username', ''))) {
            $query->where('username', 'like', '%'.$value.'%');
        }
        if ($value = trim((string) $request->query('nickname', ''))) {
            $query->where('nickname', 'like', '%'.$value.'%');
        }
        if ($value = trim((string) $request->query('email', ''))) {
            $query->where('email', 'like', '%'.$value.'%');
        }

        $rows = $query->paginate(15)->withQueryString();
        $groupMap = $this->groupNameMap();

        return $this->adminView('admin.auth.admins.index', [
            'pageTitle' => '管理员管理',
            'pageDescription' => '迁移版后台管理员列表，先支持基础账号维护和角色归属。',
            'rows' => $rows,
            'groupMap' => $groupMap,
            'selfId' => $this->auth->user()?->id,
        ]);
    }

    public function create(): View
    {
        return $this->adminView('admin.auth.admins.form', [
            'pageTitle' => '新增管理员',
            'pageDescription' => '创建新的后台管理员账号。',
            'admin' => null,
            'selectedGroupIds' => [],
            'groups' => $this->availableGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'alpha_dash', 'min:3', 'max:20', Rule::unique('admin', 'username')],
            'nickname' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'min:6', 'max:50'],
            'remark' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:normal,hidden'],
            'group_id' => ['required', 'array', 'min:1'],
            'group_id.*' => ['integer'],
        ]);

        $groupIds = $this->filterGroupIds($data['group_id']);
        if ($groupIds === []) {
            return back()->withErrors(['group_id' => '请选择可管理的角色组'])->withInput();
        }

        DB::transaction(function () use ($data, $groupIds): void {
            $salt = substr(bin2hex(random_bytes(5)), 0, 10);
            $admin = AdminUser::query()->create([
                'username' => $data['username'],
                'nickname' => (string) ($data['nickname'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
                'avatar' => (string) ($data['avatar'] ?? ''),
                'password' => $this->auth->hashPassword($data['password'], $salt),
                'salt' => $salt,
                'remark' => (string) ($data['remark'] ?? ''),
                'status' => $data['status'],
                'create_time' => time(),
                'update_time' => time(),
            ]);

            foreach ($groupIds as $groupId) {
                AuthGroupAccess::query()->create([
                    'admin_id' => $admin->id,
                    'group_id' => $groupId,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
        });

        return redirect()->route('admin.admin-users.index')->with('status', '管理员已创建');
    }

    public function edit(AdminUser $admin): View
    {
        abort_unless(in_array((int) $admin->id, $this->auth->manageableAdminIds(), true), 403);

        return $this->adminView('admin.auth.admins.form', [
            'pageTitle' => '编辑管理员',
            'pageDescription' => '修改管理员基础信息和角色归属。',
            'admin' => $admin,
            'selectedGroupIds' => AuthGroupAccess::query()->where('admin_id', $admin->id)->pluck('group_id')->map(fn ($id) => (int) $id)->all(),
            'groups' => $this->availableGroups(),
        ]);
    }

    public function update(Request $request, AdminUser $admin): RedirectResponse
    {
        abort_unless(in_array((int) $admin->id, $this->auth->manageableAdminIds(), true), 403);

        $data = $request->validate([
            'username' => ['required', 'alpha_dash', 'min:3', 'max:20', Rule::unique('admin', 'username')->ignore($admin->id)],
            'nickname' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'min:6', 'max:50'],
            'remark' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:normal,hidden'],
            'group_id' => ['required', 'array', 'min:1'],
            'group_id.*' => ['integer'],
        ]);

        $groupIds = $this->filterGroupIds($data['group_id']);
        if ($groupIds === []) {
            return back()->withErrors(['group_id' => '请选择可管理的角色组'])->withInput();
        }

        DB::transaction(function () use ($admin, $data, $groupIds): void {
            $payload = [
                'username' => $data['username'],
                'nickname' => (string) ($data['nickname'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
                'avatar' => (string) ($data['avatar'] ?? ''),
                'remark' => (string) ($data['remark'] ?? ''),
                'status' => $data['status'],
                'update_time' => time(),
            ];

            if (! empty($data['password'])) {
                $salt = substr(bin2hex(random_bytes(5)), 0, 10);
                $payload['salt'] = $salt;
                $payload['password'] = $this->auth->hashPassword($data['password'], $salt);
            }

            $admin->update($payload);

            AuthGroupAccess::query()->where('admin_id', $admin->id)->delete();
            foreach ($groupIds as $groupId) {
                AuthGroupAccess::query()->create([
                    'admin_id' => $admin->id,
                    'group_id' => $groupId,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
        });

        return redirect()->route('admin.admin-users.index')->with('status', '管理员已更新');
    }

    public function destroy(AdminUser $admin): RedirectResponse
    {
        abort_unless(in_array((int) $admin->id, $this->auth->manageableAdminIds(false), true), 403);
        abort_if($this->auth->user()?->id === $admin->id, 422, '不能删除当前登录账号');

        DB::transaction(function () use ($admin): void {
            AuthGroupAccess::query()->where('admin_id', $admin->id)->delete();
            $admin->delete();
        });

        return redirect()->route('admin.admin-users.index')->with('status', '管理员已删除');
    }

    private function availableGroups(): array
    {
        return AuthGroup::query()
            ->whereIn('id', $this->auth->manageableGroupIds())
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get()
            ->map(function (AuthGroup $group): array {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'parent_id' => $group->parent_id,
                ];
            })
            ->all();
    }

    private function filterGroupIds(array $ids): array
    {
        $allowed = $this->auth->manageableGroupIds();
        return array_values(array_intersect($allowed, array_map('intval', $ids)));
    }

    private function groupNameMap(): array
    {
        $links = AuthGroupAccess::query()->get(['admin_id', 'group_id']);
        $names = AuthGroup::query()->pluck('name', 'id');
        $map = [];

        foreach ($links as $link) {
            $map[$link->admin_id] ??= [];
            if (isset($names[$link->group_id])) {
                $map[$link->admin_id][] = $names[$link->group_id];
            }
        }

        return array_map(static fn ($items) => implode(',', $items), $map);
    }
}
