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

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'text' => '单行文本',
            'textarea' => '多行文本',
            'editor' => '编辑器',
            'number' => '数字',
            'url' => '链接',
            'image' => '图片',
            'images' => '图集',
            'file' => '文件',
            'date' => '日期',
            'datetime' => '日期时间',
            'select' => '下拉',
            'radio' => '单选',
            'checkbox' => '多选',
            'switch' => '开关',
            'map' => '地图',
            'entry' => '关联文档',
            default => $type,
        };
    }
}
