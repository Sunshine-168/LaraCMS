<?php

namespace App\Events;

use App\Models\Cms\CmsForm;
use App\Models\Cms\FormSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public FormSubmission $submission,
        public CmsForm $form,
    ) {}
}
