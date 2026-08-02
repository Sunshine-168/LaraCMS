<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    protected $table = 'cms_admin_logs';

    protected $fillable = [
        'admin_id', 'admin_name', 'method', 'path', 'ip', 'status',
    ];
}
