<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return AdminUser::query()->with('adminRole')->orderByDesc('id')->paginate($perPage);
    }

    public function roles()
    {
        return AdminRole::query()->orderByDesc('is_super')->orderBy('id')->get();
    }

    public function save(?AdminUser $user, array $input): array
    {
        $user ??= new AdminUser;
        $user->name = $input['name'] ?? $user->name;
        $user->email = $input['email'] ?? $user->email;
        $user->role_id = (int) ($input['role_id'] ?? $user->role_id);
        $user->is_active = ! empty($input['is_active']);
        // 表单未勾选任何栏目时传空数组 → 视为全部栏目
        if (array_key_exists('allowed_category_ids', $input) || array_key_exists('_has_category_scope', $input)) {
            $ids = array_values(array_filter(array_map('intval', (array) ($input['allowed_category_ids'] ?? []))));
            $user->allowed_category_ids = $ids === [] ? null : $ids;
        }

        if (! empty($input['password'])) {
            $user->password = $input['password'];
        } elseif (! $user->exists) {
            return Result::fail('新建管理员必须设置密码');
        }

        if (! AdminRole::query()->whereKey($user->role_id)->exists()) {
            return Result::fail('请选择有效角色');
        }

        $user->save();

        $role = AdminRole::query()->find($user->role_id);
        if ($role?->is_super) {
            $user->sites()->detach();
        } elseif (array_key_exists('site_ids', $input) || array_key_exists('_has_site_scope', $input)) {
            $siteIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['site_ids'] ?? [])))));
            $user->sites()->sync($siteIds);
        }

        return Result::ok($user, '管理员已保存');
    }

    public function delete(AdminUser $user, ?int $actorId = null): array
    {
        if ($actorId && $user->id === $actorId) {
            return Result::fail('不能删除当前登录账号');
        }
        if ($user->isSuperAdmin() && AdminUser::query()->whereHas('adminRole', fn ($q) => $q->where('is_super', true))->count() <= 1) {
            return Result::fail('至少保留一位超级管理员');
        }
        $user->delete();

        return Result::ok(null, '已删除');
    }
}
