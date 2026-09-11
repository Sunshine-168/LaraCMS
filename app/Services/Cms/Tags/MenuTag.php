<?php

namespace App\Services\Cms\Tags;

use App\Services\Cms\MenuQueryService;
use Illuminate\Support\Collection;

class MenuTag
{
    public function __construct(
        private readonly MenuQueryService $menus,
        private readonly ChannelTag $channels,
    ) {}

    /** @param  array<string, mixed>  $options */
    public function get(array $options = []): Collection
    {
        $location = (string) ($options['location'] ?? 'header');
        $items = $this->menus->items($location);
        if ($items->isNotEmpty()) {
            return $items;
        }

        return $this->channels->get([
            'type' => $options['type'] ?? 'top',
            'ismenu' => $options['ismenu'] ?? 1,
            'num' => $options['num'] ?? null,
        ])->each(function ($cat) {
            $cat->setAttribute('resolved_url', $cat->frontUrl());
            $cat->setRelation('children', collect());
        });
    }
}
