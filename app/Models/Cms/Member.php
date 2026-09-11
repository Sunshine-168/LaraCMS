<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class Member extends Authenticatable
{
    use BelongsToSite;

    protected $table = 'cms_members';

    protected $fillable = [
        'site_id', 'name', 'avatar', 'phone', 'bio', 'email', 'password', 'is_active', 'group_id',
        'reset_token', 'reset_token_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'reset_token', 'api_token'];

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

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'member_id');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'member_id');
    }

    public function contributedEntries(): HasMany
    {
        return $this->hasMany(Entry::class, 'member_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(MemberFavorite::class, 'member_id');
    }

    public function favoriteEntries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'cms_member_favorites', 'member_id', 'entry_id')
            ->withTimestamps()
            ->withPivot('id');
    }

    public function hasFavorited(int $entryId): bool
    {
        return $this->favorites()->where('entry_id', $entryId)->exists();
    }

    public function issueApiToken(): string
    {
        $plain = 'mbr_'.Str::random(48);
        $this->api_token = hash('sha256', $plain);
        $this->save();

        return $plain;
    }

    public function clearApiToken(): void
    {
        $this->api_token = null;
        $this->save();
    }

    public static function findByApiToken(string $plain): ?self
    {
        $plain = trim($plain);
        if ($plain === '') {
            return null;
        }

        return static::query()
            ->where('is_active', true)
            ->where('api_token', hash('sha256', $plain))
            ->first();
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
