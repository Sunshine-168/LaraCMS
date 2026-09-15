<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function paginate(int $perPage = 20, ?string $keyword = null, ?int $roleId = null, bool $inactiveOnly = false): LengthAwarePaginator
    {
        $query = AdminUser::query()->with(['adminRole', 'sites']);
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }
        if ($roleId) {
            $query->where('role_id', $roleId);
        }
        if ($inactiveOnly) {
            $query->where('is_active', false);
        }

        return $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function roles()
    {
        return AdminRole::query()->orderByDesc('is_super')->orderBy('id')->get();
    }

    public function superAdminCount(): int
    {
        return AdminUser::query()->whereHas('adminRole', fn ($q) => $q->where('is_super', true))->count();
    }

    /** @param  array<string, mixed>  $input */
    public function save(?AdminUser $user, array $input): array
    {
        $user ??= new AdminUser;
        $creating = ! $user->exists;
        $wasSuper = $user->exists && $user->isSuperAdmin();

        $name = trim((string) ($input['name'] ?? $user->name ?? ''));
        if ($name === '') {
            return Result::fail('请填写名称');
        }
        $email = trim((string) ($input['email'] ?? $user->email ?? ''));
        if ($email === '') {
            return Result::fail('请填写登录邮箱');
        }

        $user->name = $name;
        $user->email = $email;
        $user->role_id = (int) ($input['role_id'] ?? $user->role_id);

        if (array_key_exists('is_active', $input) || ! empty($input['from_form'])) {
            $user->is_active = ! empty($input['is_active']);
        } elseif ($creating) {
            $user->is_active = true;
        }

        if (array_key_exists('allowed_category_ids', $input) || array_key_exists('_has_category_scope', $input)) {
            $ids = array_values(array_filter(array_map('intval', (array) ($input['allowed_category_ids'] ?? []))));
            $user->allowed_category_ids = $ids === [] ? null : $ids;
        }

        if (! empty($input['password'])) {
            $user->password = $input['password'];
        } elseif ($creating) {
            return Result::fail('请设置登录密码');
        }

        $role = AdminRole::query()->find($user->role_id);
        if (! $role) {
            return Result::fail('请选择角色');
        }
        if ($wasSuper && ! $role->is_super && $this->superAdminCount() <= 1) {
            return Result::fail('至少保留一位超级管理员');
        }

        $user->save();

        if ($role->is_super) {
            $user->sites()->detach();
        } elseif (array_key_exists('site_ids', $input) || array_key_exists('_has_site_scope', $input)) {
            $siteIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['site_ids'] ?? [])))));
            $user->sites()->sync($siteIds);
        } elseif ($creating) {
            $siteId = CurrentSite::idOrDefault() ?? Site::default()?->id;
            if ($siteId) {
                $user->sites()->sync([(int) $siteId]);
            }
        }

        return Result::ok($user, $creating ? '已添加' : '已保存');
    }

    public function toggle(AdminUser $user, ?int $actorId = null): array
    {
        if ($actorId && (int) $user->id === $actorId && $user->is_active) {
            return Result::fail('不能停用当前登录账号');
        }
        if ($user->is_active && $user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return Result::fail('至少保留一位能登录的超级管理员');
        }
        $user->is_active = ! $user->is_active;
        $user->save();

        return Result::ok(
            $user,
            $user->is_active ? '已启用，可以登录后台' : '已停用，不能登录后台'
        );
    }

    public function delete(AdminUser $user, ?int $actorId = null): array
    {
        if ($actorId && (int) $user->id === $actorId) {
            return Result::fail('不能删除当前登录账号');
        }
        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return Result::fail('至少保留一位超级管理员');
        }
        $name = $user->name ?: $user->email;
        $user->delete();

        return Result::ok(null, '已删除「'.$name.'」');
    }
}
