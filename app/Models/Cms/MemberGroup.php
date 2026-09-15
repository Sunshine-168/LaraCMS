<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberGroup extends Model
{
    use BelongsToSite;

    protected $table = 'cms_member_groups';

    protected $fillable = ['site_id', 'name', 'slug', 'sort', 'is_active', 'permissions'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /** @return array<string, string> */
    public static function permissionLabels(): array
    {
        return [
            'download' => '下载受保护文件',
            'form_submit' => '提交表单',
            'view_member_content' => '看会员内容',
            'upload' => '上传文件',
            'contribute' => '前台投稿',
        ];
    }

    /** @return list<string> */
    public static function permissionKeys(): array
    {
        return array_keys(self::permissionLabels());
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
