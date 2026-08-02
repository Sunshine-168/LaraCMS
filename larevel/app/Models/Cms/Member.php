<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    use BelongsToSite;

    protected $table = 'cms_members';

    protected $fillable = [
        'site_id', 'name', 'avatar', 'email', 'password', 'is_active', 'group_id',
        'reset_token', 'reset_token_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'reset_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'reset_token_expires_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MemberGroup::class, 'group_id');
    }

    public function canMember(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $this->loadMissing('group');
        if (! $this->group) {
            return true;
        }

        return $this->group->allows($permission);
    }
}
