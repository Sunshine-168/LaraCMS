<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminPermission;
use App\Models\Admin\AdminRole;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleAdminService
{
    public function all(): Collection
    {
        return AdminRole::query()->withCount('users', 'permissions')->orderByDesc('is_super')->orderBy('id')->get();
    }

    public function permissionTree(): Collection
    {
        return AdminPermission::query()->orderBy('sort')->get()->groupBy('group');
    }

    /** @param  array<string, mixed>  $input */
    public function save(?AdminRole $role, array $input): array
    {
        $role ??= new AdminRole;
        $creating = ! $role->exists;
        $keepSuper = $role->exists && $role->is_super;

        $name = trim((string) ($input['name'] ?? $role->name ?? ''));
        if ($name === '') {
            return Result::fail('请填写名称');
        }
        $role->name = $name;
        $role->remark = trim((string) ($input['remark'] ?? '')) ?: null;
        $role->is_super = $keepSuper;

        if ($creating || trim((string) $role->slug) === '') {
            $slugInput = trim((string) ($input['slug'] ?? ''));
            if ($slugInput !== '') {
                $slug = Str::slug($slugInput, '_');
                $slug = $slug !== '' ? $slug : $slugInput;
            } else {
                $slug = Str::slug($name, '_');
                if ($slug === '') {
                    $slug = 'r'.substr(md5($name), 0, 8);
                }
            }
            $role->slug = $this->uniqueSlug($slug, $role->exists ? (int) $role->id : null);
        }

        $role->save();

        if (! $role->is_super) {
            $keys = array_values(array_filter(array_map('strval', (array) ($input['permissions'] ?? []))));
            $ids = AdminPermission::query()->whereIn('key', $keys)->pluck('id');
            $role->permissions()->sync($ids);
        } else {
            $role->permissions()->detach();
        }

        return Result::ok($role, $creating ? '已添加' : '已保存');
    }

    public function delete(AdminRole $role): array
    {
        if ($role->is_super) {
            return Result::fail('超级管理员角色不能删除');
        }
        $n = $role->users()->count();
        if ($n > 0) {
            return Result::fail('还有 '.$n.' 人在用这个角色，请先到管理员里换掉');
        }
        $name = $role->name ?: $role->slug;
        $role->permissions()->detach();
        $role->delete();

        return Result::ok(null, '已删除「'.$name.'」');
    }

    protected function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = $base;
        $i = 2;
        while (AdminRole::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }
}
