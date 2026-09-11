<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminQuickPanel extends Model
{
    protected $table = 'admin_quick_panels';

    protected $fillable = ['admin_user_id', 'item_key', 'sort'];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
