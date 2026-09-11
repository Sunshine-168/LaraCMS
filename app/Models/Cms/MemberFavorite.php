<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberFavorite extends Model
{
    use BelongsToSite;

    protected $table = 'cms_member_favorites';

    protected $fillable = ['site_id', 'member_id', 'entry_id'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'entry_id');
    }
}
