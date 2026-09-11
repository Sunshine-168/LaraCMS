<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /** 绑定栏目；无绑定时表示该模型下全栏目可用 */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'cms_model_field_category', 'model_field_id', 'category_id');
    }

    /** 供表单 data-bound-cats：* = 全栏目 */
    public function boundCatsAttr(): string
    {
        $this->loadMissing('categories');
        if ($this->categories->isEmpty()) {
            return '*';
        }

        return $this->categories->pluck('id')->implode(',');
    }

    /** 关联内容模型 slug（entry 类型，或 manga_id 兼容） */
    public function relatedModelSlug(): ?string
    {
        $slug = $this->options['related_model'] ?? null;
        if (is_string($slug) && $slug !== '') {
            return $slug;
        }
        if ($this->type === 'entry' || $this->name === 'manga_id') {
            return $this->name === 'manga_id' ? 'manga' : null;
        }

        return null;
    }
}
