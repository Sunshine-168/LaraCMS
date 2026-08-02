<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AdminPanel extends Model
{
    protected $table = 'admin_panel';

    public $timestamps = false;

    protected $guarded = [];
}
