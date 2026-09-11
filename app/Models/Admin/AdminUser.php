<?php

namespace App\Models\Admin;

use App\Models\Cms\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'is_active', 'allowed_category_ids',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /** @var list<string>|null */
    protected ?array $permissionKeysCache = null;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'allowed_category_ids' => 'array',
        ];
    }

    /** null = 全部栏目；数组 = 仅这些栏目 ID */
    public function allowedCategoryIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }
        $ids = $this->allowed_category_ids;
        if (! is_array($ids) || $ids === []) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'admin_user_site', 'admin_user_id', 'site_id');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->adminRole?->is_super;
    }

    /** 模块级权限：超管放行，否则看角色权限表 */
    public function canPerm(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($key, $this->permissionKeys(), true);
    }

    /** @return list<string> */
    public function permissionKeys(): array
    {
        if ($this->permissionKeysCache !== null) {
            return $this->permissionKeysCache;
        }

        if ($this->isSuperAdmin()) {
            return $this->permissionKeysCache = AdminPermission::query()->orderBy('sort')->pluck('key')->all();
        }

        $this->loadMissing('adminRole.permissions');

        return $this->permissionKeysCache = $this->adminRole?->permissions
            ?->pluck('key')
            ->values()
            ->all() ?? [];
    }
}
