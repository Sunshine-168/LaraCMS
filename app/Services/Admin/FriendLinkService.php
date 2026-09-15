<?php

namespace App\Services\Admin;

use App\Models\Cms\Recommend;
use App\Models\Cms\RecommendItem;
use App\Services\Cms\HtmlCacheService;
use App\Support\Locale;
use App\Support\Result;
use App\Support\Translatable;
use Illuminate\Support\Collection;

/** 首页友情链接：复用推荐位 partners / friend_links，不是导航站的链接模型 */
class FriendLinkService
{
    public const NAMES = ['partners', 'friend_links'];

    public function __construct(private readonly HtmlCacheService $htmlCache) {}

    public function block(): Recommend
    {
        $row = Recommend::query()->where('name', 'partners')->first()
            ?? Recommend::query()->where('name', 'friend_links')->first();
        if ($row) {
            if ($row->type !== 'link') {
                $row->type = 'link';
                $row->save();
            }

            return $row;
        }

        return Recommend::query()->create([
            'name' => 'partners',
            'title' => '友情链接',
            'type' => 'link',
            'is_active' => true,
        ]);
    }

    /** @return Collection<int, RecommendItem> */
    public function items(): Collection
    {
        return $this->block()->items()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function owned(RecommendItem $item): RecommendItem
    {
        if ((int) $item->recommend_id !== (int) $this->block()->id) {
            abort(404);
        }

        return $item;
    }

    /** @param  array<string, mixed>  $input */
    public function add(array $input): array
    {
        $titleBag = $this->packTitle($input);
        if (is_string($titleBag)) {
            return Result::fail($titleBag);
        }
        $url = $this->normalizeUrl((string) ($input['url'] ?? ''));
        if ($url === '') {
            return Result::fail('请填写网址');
        }

        $block = $this->block();
        $sort = array_key_exists('sort_order', $input)
            ? (int) $input['sort_order']
            : (int) $block->items()->max('sort_order') + 10;
        $item = RecommendItem::query()->create([
            'recommend_id' => $block->id,
            'title' => $titleBag,
            'url' => $url,
            'image' => $this->image($input),
            'sort_order' => $sort,
            'is_active' => array_key_exists('is_active', $input) ? ! empty($input['is_active']) : true,
        ]);
        $this->bust($block);

        return Result::ok($item, '已添加');
    }

    /** @param  array<string, mixed>  $input */
    public function save(RecommendItem $item, array $input): array
    {
        $this->owned($item);
        $titleBag = $this->packTitle($input, $item);
        if (is_string($titleBag)) {
            return Result::fail($titleBag);
        }
        $url = $this->normalizeUrl((string) ($input['url'] ?? ''));
        if ($url === '') {
            return Result::fail('请填写网址');
        }

        $item->title = $titleBag;
        $item->url = $url;
        $item->image = $this->image($input);
        $item->is_active = ! empty($input['is_active']);
        if (array_key_exists('sort_order', $input)) {
            $item->sort_order = (int) $input['sort_order'];
        }
        $item->save();
        $this->bust($item->recommend);

        return Result::ok($item, '已保存');
    }

    public function toggle(RecommendItem $item): array
    {
        $this->owned($item);
        $item->is_active = ! $item->is_active;
        $item->save();
        $this->bust($item->recommend);

        return Result::ok($item, $item->is_active ? '已启用，前台会再显示' : '已停用，前台不再显示');
    }

    public function delete(RecommendItem $item): array
    {
        $this->owned($item);
        $label = (string) $item->t('title');
        $block = $item->recommend;
        $item->delete();
        $this->bust($block);

        return Result::ok(null, $label !== '' ? '已删除「'.$label.'」' : '已删除');
    }

    protected function bust(?Recommend $block): void
    {
        if ($block) {
            $this->htmlCache->forgetAll('recommend:'.$block->id);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>|string
     */
    protected function packTitle(array $input, ?RecommendItem $item = null): array|string
    {
        $packed = Translatable::packTranslations(
            (array) ($input['locales'] ?? []),
            ['title'],
            $item ? Translatable::existingTranslations($item, ['title']) : []
        );
        $row = [];
        foreach ($packed['title'] ?? [] as $code => $value) {
            $row['title_'.$code] = $value;
        }
        $quick = trim((string) ($input['title'] ?? ''));
        if ($quick !== '') {
            $row['title_'.Locale::default()] = $quick;
        }
        if (! Translatable::keyedLocalesHaveText($row, 'title')) {
            return '请填写网站名称';
        }

        return Translatable::packKeyedLocales($row, 'title');
    }

    /** @param  array<string, mixed>  $input */
    protected function image(array $input): ?string
    {
        $image = trim((string) ($input['image'] ?? ''));

        return $image !== '' ? $image : null;
    }

    protected function normalizeUrl(string $raw): string
    {
        $url = trim($raw);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url) || str_starts_with($url, '/')) {
            return $url;
        }

        return 'https://'.$url;
    }
}
