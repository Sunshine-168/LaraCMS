<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsForm extends Model
{
    use BelongsToSite;

    protected $table = 'cms_forms';

    protected $fillable = ['site_id', 'slug', 'name', 'fields', 'is_active', 'notify_email'];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    /** @return array<string, string> */
    public static function fieldTypes(): array
    {
        return [
            'text' => '单行文本',
            'email' => '邮箱',
            'tel' => '电话',
            'number' => '数字',
            'textarea' => '多行文本',
            'select' => '下拉',
            'radio' => '单选',
            'checkbox' => '多选',
        ];
    }

    public static function fieldTypeLabel(?string $type): string
    {
        $type = (string) $type;

        return self::fieldTypes()[$type] ?? ($type !== '' ? $type : '单行文本');
    }

    public function fieldCount(): string
    {
        $n = count($this->fields ?? []);

        return $n > 0 ? $n.' 个字段' : '还没有字段';
    }

    public function inboxCount(): string
    {
        $n = (int) ($this->submissions_count ?? 0);

        return $n > 0 ? $n.' 条' : '还没有留言';
    }
}
