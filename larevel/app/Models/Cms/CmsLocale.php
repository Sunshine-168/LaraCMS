<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;

class CmsLocale extends Model
{
    use BelongsToSite;

    protected $table = 'cms_locales';

    protected $fillable = ['site_id', 'code', 'label', 'native', 'is_default', 'hide_default', 'is_active', 'sort',];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'hide_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
