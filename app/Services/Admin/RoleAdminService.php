<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPermission;
use App\Models\Admin\AdminRole;
use App\Support\Result;
use Illuminate\Support\Str;

class RoleAdminService
{
    public function all()
    {
        return AdminRole::query()->withCount('users', 'permissions')->orderByDesc('is_super')->orderBy('id')->get();
    }

    public function permissionTree()
    {
        return AdminPermission::query()->orderBy('sort')->get()->groupBy('group');
    }

    public function save(?AdminRole $role, array $input): array
    {
        $role ??= new AdminRole;
        $isNew = ! $role->exists;

        if ($role->exists && $role->is_super && empty($input['is_super'])) {
            return Result::fail('不能取消超级管理员标记');
        }

        $role->name = $input['name'] ?? $role->name;
        if ($isNew || empty($role->slug)) {
            $slug = trim((string) ($input['slug'] ?? ''));
            $role->slug = $slug !== '' ? Str::slug($slug, '_') : Str::slug($role->name, '_');
        }
        $role->remark = $input['remark'] ?? null;
        $role->is_super = ! empty($input['is_super']);
        $role->save();

        if (! $role->is_super) {
            $keys = array_values(array_filter((array) ($input['permissions'] ?? [])));
            $ids = AdminPermission::query()->whereIn('key', $keys)->pluck('id');
            $role->permissions()->sync($ids);
        } else {
            $role->permissions()->detach();
        }

        return Result::ok($role, '角色已保存');
    }

    public function delete(AdminRole $role): array
    {
        if ($role->is_super) {
            return Result::fail('不能删除超级管理员角色');
        }
        if ($role->users()->exists()) {
            return Result::fail('该角色下仍有管理员，请先调整');
        }
        $role->permissions()->detach();
        $role->delete();

        return Result::ok(null, '已删除');
    }
}
