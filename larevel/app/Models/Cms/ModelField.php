<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelField extends Model
{
    protected $table = 'cms_model_fields';

    protected $fillable = [
        'model_id', 'name', 'label', 'type', 'options',
        'is_required', 'is_filter', 'is_list', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_filter' => 'boolean',
            'is_list' => 'boolean',
        ];
    }

    public function modelDef(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }
}
