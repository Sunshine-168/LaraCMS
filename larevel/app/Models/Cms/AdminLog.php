<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use BelongsToSite;

    protected $table = 'cms_admin_logs';

    protected $fillable = ['site_id', 'admin_id', 'admin_name', 'method', 'path', 'ip', 'status',];
}
