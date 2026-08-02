<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Category;
use Illuminate\Support\HtmlString;

class BreadcrumbTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function render(array $options = []): HtmlString
    {
        $symbol = (string) ($options['symbol'] ?? ' / ');
        $class = (string) ($options['class'] ?? 'breadcrumb');
        $currentStyle = (string) ($options['currentstyle'] ?? '');
        $isClick = (bool) ($options['isclick'] ?? true);

        $catid = $options['catid'] ?? $this->context->category()?->id;
        $category = $catid ? Category::query()->active()->find((int) $catid) : $this->context->category();

        $parts = [
            '<a href="'.e(route('cms.home')).'">首页</a>',
        ];

        foreach ($this->ancestors($category) as $item) {
            $parts[] = '<a href="'.e($item->url).'">'.e($item->title).'</a>';
        }

        if ($category) {
            if ($isClick && ! $this->context->article()) {
                $label = e($category->title);
                $parts[] = $currentStyle !== ''
                    ? '<span class="'.e($currentStyle).'">'.$label.'</span>'
                    : $label;
            } else {
                $parts[] = '<a href="'.e($category->url).'">'.e($category->title).'</a>';
            }
        }

        if ($article = $this->context->article()) {
            $label = e($article->title);
            $parts[] = $currentStyle !== ''
                ? '<span class="'.e($currentStyle).'">'.$label.'</span>'
                : $label;
        }

        return new HtmlString(
            '<nav class="'.e($class).'">'.implode(e($symbol), $parts).'</nav>'
        );
    }

    /**
     * @return list<Category>
     */
    private function ancestors(?Category $category): array
    {
        if (! $category) {
            return [];
        }

        $chain = [];
        $parentId = (int) $category->parent_id;
        $guard = 0;

        while ($parentId > 0 && $guard < 20) {
            $parent = Category::query()->active()->find($parentId);
            if (! $parent) {
                break;
            }
            array_unshift($chain, $parent);
            $parentId = (int) $parent->parent_id;
            $guard++;
        }

        return $chain;
    }
}
