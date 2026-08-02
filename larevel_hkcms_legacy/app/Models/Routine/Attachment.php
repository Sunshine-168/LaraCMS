<?php

namespace App\Models\Routine;

use App\Models\Admin\AdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'attachment';

    public $timestamps = false;

    protected $guarded = [];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'normal');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

    public function previewUrl(): string
    {
        if (preg_match('#^https?://#i', (string) $this->path)) {
            return (string) $this->path;
        }

        return (string) $this->path;
    }

    public function sizeText(): string
    {
        $size = (int) $this->size;

        if ($size >= 1024 * 1024) {
            return round($size / 1024 / 1024, 2).' MB';
        }

        if ($size >= 1024) {
            return round($size / 1024, 2).' KB';
        }

        return $size.' B';
    }
}
