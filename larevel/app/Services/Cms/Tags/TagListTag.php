<?php

namespace App\Services\Cms\Tags;

use App\Models\Cms\Tag;
use Illuminate\Support\Collection;

class TagListTag
{
    /** @param  array<string, mixed>  $options */
    public function get(array $options = []): Collection
    {
        $num = max(1, (int) ($options['num'] ?? 20));

        return Tag::query()->orderByDesc('id')->limit($num)->get();
    }
}
