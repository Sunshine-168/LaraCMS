<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberGroup extends Model
{
    protected $table = 'cms_member_groups';

    protected $fillable = ['name', 'slug', 'sort', 'is_active', 'permissions'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /** @return list<string> */
    public static function permissionKeys(): array
    {
        return ['download', 'form_submit', 'view_member_content'];
    }

    public function allows(string $key): bool
    {
        $perms = $this->permissions;
        if (! is_array($perms) || $perms === []) {
            return true; // 未配置视为全开，兼容旧数据
        }

        return in_array($key, $perms, true);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'group_id');
    }
}
