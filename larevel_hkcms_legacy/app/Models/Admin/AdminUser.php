<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    protected $table = 'admin';

    public $timestamps = false;

    protected $guarded = [];

    public function avatarUrl(): string
    {
        if (! empty($this->avatar)) {
            return $this->avatar;
        }

        $label = $this->nickname ?: $this->username;

        return 'https://ui-avatars.com/api/?name='.urlencode($label).'&background=e5e7eb&color=111827';
    }
}
