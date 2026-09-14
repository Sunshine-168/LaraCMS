<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use BelongsToSite;

    protected $table = 'cms_menus';

    protected $fillable = ['site_id', 'name', 'location', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'menu_id')->orderBy('sort_order');
    }

    /** @return array<string, string> */
    public static function locations(): array
    {
        return [
            'header' => '页头',
            'footer' => '页脚',
            'sidebar' => '侧栏',
            'mobile' => '移动端',
        ];
    }

    public static function locationLabel(?string $location): string
    {
        $location = (string) $location;

        return self::locations()[$location] ?? ($location !== '' ? $location : '—');
    }

    public function listingCount(): string
    {
        $n = (int) ($this->items_count ?? $this->items->count());

        return $n > 0 ? $n.' 项' : '还没有条目';
    }
}
