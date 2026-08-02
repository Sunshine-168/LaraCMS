<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AuthGroupAccess extends Model
{
    protected $table = 'auth_group_access';

    public $timestamps = false;

    protected $guarded = [];
}
