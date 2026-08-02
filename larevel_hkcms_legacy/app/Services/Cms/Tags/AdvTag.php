<?php

namespace App\Services\Cms\Tags;

use App\Models\Cms\Banner;
use App\Models\Cms\Recommend;
use App\Services\Cms\Api\ContentApiService;
use App\Services\Cms\SiteService;
use Illuminate\Support\Collection;

class AdvTag
{
    public function __construct(
        private readonly SiteService $siteService,
        private readonly ContentApiService $contentApiService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, mixed>
     */
    public function get(array $options = []): Collection
    {
        $name = (string) ($options['name'] ?? '');

        if ($name === '') {
            return collect();
        }

        $lang = (string) ($options['lang'] ?? $this->siteService->locale());

        $recommend = Recommend::query()
            ->active()
            ->where('name', $name)
            ->where(function ($query) use ($lang): void {
                $query->where('lang', $lang)->orWhereIn('lang', ['-1', -1]);
            })
            ->orderByRaw('case when lang = ? then 0 else 1 end', [$lang])
            ->first();

        if (! $recommend) {
            return collect();
        }

        $num = isset($options['num']) ? (int) $options['num'] : null;

        if ((string) $recommend->type === '4') {
            $payload = json_decode((string) $recommend->value_id, true);
            $rows = is_array($payload)
                ? $this->contentApiService->fetchRecommendContent($payload, $lang)
                : [];

            return collect($rows)->take($num ?: count($rows))->values();
        }

        if (! empty($options['itemid']) && is_numeric($options['itemid'])) {
            $item = Banner::query()
                ->active()
                ->where('recommend_id', $recommend->id)
                ->where('id', (int) $options['itemid'])
                ->first();

            return $item ? collect([$item]) : collect();
        }

        $query = Banner::query()
            ->active()
            ->where('recommend_id', $recommend->id)
            ->orderBy('weigh')
            ->orderByDesc('id');

        if ($num !== null && $num > 0) {
            $query->limit($num);
        }

        return $query->get();
    }
}
