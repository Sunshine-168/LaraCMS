<?php

namespace App\Services\Cms\Tags;

use App\Models\Cms\Entry;
use Illuminate\Support\Collection;

class ArconeTag
{
    /** @param  array<string, mixed>  $options */
    public function get(array $options = []): Collection
    {
        $query = Entry::query()->with(['category', 'modelDef'])->published();

        if (! empty($options['aid'])) {
            $query->where('id', (int) $options['aid']);
        } elseif (! empty($options['id'])) {
            $query->where('id', (int) $options['id']);
        } else {
            return collect();
        }

        $item = $query->first();

        return $item ? collect([$item]) : collect();
    }
}
