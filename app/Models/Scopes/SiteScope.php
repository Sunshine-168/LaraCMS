<?php

namespace App\Models\Scopes;

use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SiteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $siteId = CurrentSite::id();
        if ($siteId === null) {
            return;
        }
        $builder->where($model->getTable().'.site_id', $siteId);
    }
}
