<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AuthGroup extends Model
{
    protected $table = 'auth_group';

    public $timestamps = false;

    protected $guarded = [];
}
