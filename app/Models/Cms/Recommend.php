<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recommend extends Model
{
    use BelongsToSite;

    protected $table = 'cms_recommends';

    protected $fillable = ['site_id', 'name', 'title', 'type', 'query_params', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'query_params' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecommendItem::class, 'recommend_id')->orderBy('sort_order');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'image' => '图片',
            'link' => '链接',
            'html' => 'HTML',
            'entry' => '文档',
            'query' => '自动取数',
            default => $type,
        };
    }

    public function listingCount(): string
    {
        if ($this->type === 'query') {
            $n = (int) ($this->query_params['limit'] ?? 10);

            return '自动最多 '.$n.' 篇';
        }
        $n = (int) ($this->items_count ?? $this->items->count());

        return $n > 0 ? $n.' 条' : '还没有条目';
    }
}
