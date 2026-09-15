<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use App\Support\AdminLogAction;
use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use BelongsToSite;

    protected $table = 'cms_admin_logs';

    protected $fillable = ['site_id', 'admin_id', 'admin_name', 'method', 'path', 'detail', 'ip', 'status'];

    public function actionLabel(): string
    {
        return AdminLogAction::label((string) $this->method, (string) $this->path);
    }

    public function failed(): bool
    {
        return (int) $this->status >= 400;
    }
}
