<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use BelongsToSite;

    protected $table = 'cms_form_submissions';

    protected $fillable = ['site_id', 'form_id', 'member_id', 'data', 'ip', 'is_read', 'admin_note', 'replied_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(CmsForm::class, 'form_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function guestEmail(): ?string
    {
        $data = $this->data ?? [];
        foreach (['email', 'mail', 'e-mail'] as $key) {
            $value = $data[$key] ?? null;
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }
        foreach ($this->form?->fields ?? [] as $field) {
            if (! is_array($field) || ($field['type'] ?? '') !== 'email') {
                continue;
            }
            $value = $data[$field['name'] ?? ''] ?? null;
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }

    public function fieldLabel(string $name): string
    {
        foreach ($this->form?->fields ?? [] as $field) {
            if (is_array($field) && ($field['name'] ?? '') === $name) {
                return (string) ($field['label'] ?? $name);
            }
        }

        return $name;
    }
}
