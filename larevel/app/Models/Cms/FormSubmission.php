<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use BelongsToSite;

    protected $table = 'cms_form_submissions';

    protected $fillable = ['site_id', 'form_id', 'data', 'ip'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(CmsForm::class, 'form_id');
    }
}
