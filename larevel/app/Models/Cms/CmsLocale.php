<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

class CmsLocale extends Model
{
    protected $table = 'cms_locales';

    protected $fillable = [
        'code', 'label', 'native', 'is_default', 'hide_default', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'hide_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
