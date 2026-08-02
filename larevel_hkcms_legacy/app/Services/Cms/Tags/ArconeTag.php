<?php

namespace App\Services\Cms\Tags;

use App\Models\Cms\Archive;
use Illuminate\Support\Collection;

class ArconeTag
{
    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, Archive>
     */
    public function get(array $options = []): Collection
    {
        $aid = (int) ($options['aid'] ?? $options['id'] ?? 0);

        if ($aid <= 0) {
            return collect();
        }

        $query = Archive::query()
            ->with(['category', 'modelDef'])
            ->published()
            ->where('id', $aid);

        if (! empty($options['model']) && is_numeric($options['model'])) {
            $query->where('model_id', (int) $options['model']);
        }

        $article = $query->first();

        return $article ? collect([$article]) : collect();
    }
}
