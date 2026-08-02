<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AuthRule extends Model
{
    protected $table = 'auth_rule';

    public $timestamps = false;

    protected $guarded = [];
}
