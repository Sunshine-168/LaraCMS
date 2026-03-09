<?php

namespace App\Services\Cms\Api;

use App\Models\Cms\Banner;
use App\Models\Cms\Recommend;

class RecommendApiService
{
    public function __construct(private readonly ContentApiService $contentApiService)
    {
    }

    public function contentList(array $filters): ?array
    {
        $recommend = Recommend::query()
            ->active()
            ->where('name', $filters['name'])
            ->where('lang', $filters['lang'])
            ->first();

        if (! $recommend) {
            return null;
        }

        $banner = [];

        if ((string) $recommend->type === '4') {
            $payload = json_decode((string) $recommend->value_id, true);
            $banner = is_array($payload)
                ? $this->contentApiService->fetchRecommendContent($payload, $filters['lang'])
                : [];
        } elseif (! empty($filters['itemid']) && is_numeric($filters['itemid'])) {
            $item = Banner::query()
                ->active()
                ->where('recommend_id', $recommend->id)
                ->where('id', (int) $filters['itemid'])
                ->first();

            $banner = $item ? $this->serializeBanner($item) : null;
        } else {
            $query = Banner::query()
                ->active()
                ->where('recommend_id', $recommend->id)
                ->orderBy('weigh');

            if (! empty($filters['num']) && is_numeric($filters['num'])) {
                $query->limit((int) $filters['num']);
            }

            $banner = $query->get()->map(fn (Banner $item) => $this->serializeBanner($item))->all();
        }

        return [
            'recommend' => $recommend->toArray(),
            'banner' => $banner,
        ];
    }

    private function serializeBanner(Banner $banner): array
    {
        $data = $banner->toArray();
        $data['image'] = $this->absoluteUrl($data['image'] ?? '');

        return $data;
    }

    private function absoluteUrl(?string $value): string
    {
        if (! $value) {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        return url($value);
    }
}
