<?php

namespace App\Models\Cms;

use App\Models\Admin\AdminUser;
use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryRevision extends Model
{
    use BelongsToSite;

    protected $table = 'cms_entry_revisions';

    protected $fillable = [
        'site_id', 'entry_id', 'admin_id', 'snapshot', 'title_hint',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}
