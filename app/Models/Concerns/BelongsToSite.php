<?php

namespace App\Models\Concerns;

use App\Models\Cms\Site;
use App\Models\Scopes\SiteScope;
use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSite
{
    public static function bootBelongsToSite(): void
    {
        static::addGlobalScope(new SiteScope);

        static::creating(function ($model) {
            if (empty($model->site_id)) {
                $siteId = CurrentSite::idOrDefault();
                if ($siteId) {
                    $model->site_id = $siteId;
                }
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
