<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use BelongsToSite;

    use Translatable;

    protected $table = 'cms_tags';

    protected $fillable = ['site_id', 'title', 'slug'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'slug' => 'array',
        ];
    }

    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'cms_entry_tag', 'tag_id', 'entry_id');
    }
}
